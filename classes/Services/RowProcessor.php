<?php

namespace Nottingham\ImportMapper\Services;

use Exception;
use Nottingham\ImportMapper\Models\FieldMapping;
use Nottingham\ImportMapper\Models\FieldProcessingResult;
use Nottingham\ImportMapper\Models\ImportData;
use Nottingham\ImportMapper\Models\ImportDataRow;
use Nottingham\ImportMapper\Models\ImportError;
use Nottingham\ImportMapper\Models\Mapping;
use Nottingham\ImportMapper\Models\ProjectStructure;

final class RowProcessor
{
    // Cache for resolveMatchingField(): formName::eventName
    private array $resolveMatchingFieldCache = [];

    /**
     * @param RecordIdResolver $recordIdResolver
     * @param RecordBuilder $recordBuilder
     * @param InstanceResolver $instanceResolver
     * @param DagResolver $dagResolver
     * @param DateConverter $dateConverter
     * @param ValueMapper $valueMapper
     * @param FieldCombiner $fieldCombiner
     * @param FieldRegexTransformer $fieldRegexTransformer
     */
    public function __construct(
        private readonly RecordIdResolver      $recordIdResolver,
        private readonly RecordBuilder         $recordBuilder,
        private readonly InstanceResolver      $instanceResolver,
        private readonly DagResolver           $dagResolver,
        private readonly DateConverter         $dateConverter,
        private readonly ValueMapper           $valueMapper,
        private readonly FieldCombiner         $fieldCombiner,
        private readonly FieldRegexTransformer $fieldRegexTransformer
    )
    {
    }

    /**
     * Pre-fetch all matching lookups before the row loop.
     * Runs record matching prefetch first, then instance matching for all found existing records.
     *
     * @param Mapping $mapping Mapping configuration
     * @param ProjectStructure $project Project structure
     * @param int|null $projectId Project ID
     * @param ImportData $csvData Parsed CSV chunk
     * @throws Exception
     */
    public function prefetch(Mapping $mapping, ProjectStructure $project, ?int $projectId, ImportData $csvData): void
    {
        $this->prefetchRecordMatching($mapping, $projectId, $csvData);
        $this->prefetchInstanceMatching(
            $mapping,
            $project,
            $this->recordIdResolver->getExistingRecordIds(),
            $projectId
        );
    }

    /**
     * Bulk-prefetch instance matching data for existing records.
     * Call after prefetchRecordMatching() so existing record IDs are known.
     *
     * @param Mapping $mapping Mapping configuration
     * @param ProjectStructure $project Project structure
     * @param array $existingRecordIds Record IDs that already exist in REDCap
     * @param int|null $projectId Project ID
     * @throws Exception
     */
    public function prefetchInstanceMatching(
        Mapping          $mapping,
        ProjectStructure $project,
        array            $existingRecordIds,
        ?int             $projectId = null
    ): void
    {
        if (empty($existingRecordIds)) {
            return;
        }

        $hasEventMatching = $mapping->matchingConfig->eventEnabled;
        $hasFormMatching = $mapping->matchingConfig->formEnabled;

        if (!$hasEventMatching && !$hasFormMatching) {
            return;
        }

        // Collect unique (eventName, formName, fieldName) combos that need instance prefetch
        $combos = [];

        foreach ($mapping->fieldMappings as $fieldMapping) {
            $formName = $fieldMapping->redcapFormName;
            $eventName = $fieldMapping->redcapEventName;
            $isRepeatingEvent = $project->isLongitudinal && $eventName !== null && $project->isRepeatingEvent($eventName);
            $isRepeatingForm = !$isRepeatingEvent && $project->isRepeatingForm($formName, $eventName);

            if (!$isRepeatingEvent && !$isRepeatingForm) {
                continue;
            }

            $matchingFieldId = $this->resolveMatchingField($project, $mapping, $fieldMapping);
            if ($matchingFieldId === null) {
                continue;
            }

            $matchingFieldMapping = $mapping->findFieldMappingById($matchingFieldId);
            if ($matchingFieldMapping === null) {
                continue;
            }

            $comboKey = ($eventName ?? '') . '::' . $matchingFieldMapping->redcapFieldName;
            if (!isset($combos[$comboKey])) {
                $combos[$comboKey] = [
                    'eventName' => $eventName,
                    'fieldName' => $matchingFieldMapping->redcapFieldName,
                ];
            }
        }

        foreach ($combos as $combo) {
            $this->instanceResolver->prefetch(
                $existingRecordIds,
                $combo['eventName'],
                $combo['fieldName'],
                $projectId
            );
        }
    }

    /**
     * Pre-fetch record matching data before the row loop
     *
     * @param Mapping $mapping Mapping configuration
     * @param int|null $projectId Project ID
     * @param ImportData $csvData Parsed CSV chunk
     * @throws Exception
     */
    public function prefetchRecordMatching(Mapping $mapping, ?int $projectId, ImportData $csvData): void
    {
        if (!$mapping->matchingConfig->recordEnabled || !$mapping->matchingConfig->recordFieldMappingId) {
            return;
        }

        $fieldMapping = $mapping->findFieldMappingById($mapping->matchingConfig->recordFieldMappingId);
        if ($fieldMapping === null) {
            return;
        }

        // Extract unique non-empty values for the matching field from the CSV chunk
        $csvFieldName = $fieldMapping->csvFieldName;
        $filterValues = [];
        foreach ($csvData->rows as $row) {
            $val = $row->get($csvFieldName);
            if ($val !== null && $val !== '') {
                $filterValues[] = $val;
            }
        }
        $filterValues = array_values(array_unique($filterValues));

        $this->recordIdResolver->prefetch(
            $fieldMapping->redcapFieldName,
            $fieldMapping->redcapEventName,
            $projectId,
            $filterValues
        );
    }

    /**
     * Process a single data row using ImportDataRow object
     *
     * @param ImportDataRow $dataRow row object with data and row number
     * @param int|null $projectId Project ID
     * @return array Result with 'errors' and 'records' arrays
     * @throws Exception
     */
    public function process(
        ProjectStructure $project,
        Mapping          $mapping,
        ImportDataRow    $dataRow,
        ?int             $projectId = null
    ): array
    {
        $errors = [];
        $rowNumber = $dataRow->rowNumber;

        // Get record matching field mapping if enabled
        $recordMatchingFieldMapping = null;
        if ($mapping->matchingConfig->recordEnabled && $mapping->matchingConfig->recordFieldMappingId) {
            $recordMatchingFieldMapping = $mapping->findFieldMappingById($mapping->matchingConfig->recordFieldMappingId);
        }

        try {
            $dag = $this->dagResolver->resolve($dataRow, $mapping->matchingConfig->dagConfig, array_keys($project->dataAccessGroups));
        } catch (Exception $e) {
            return [
                'errors' => [ImportError::transformation($e->getMessage(), $rowNumber)],
                'records' => []
            ];
        }

        // Resolve record ID for this row
        $recordId = $this->recordIdResolver->resolve($dataRow, $recordMatchingFieldMapping, $projectId, $project, $dag);
        if ($recordId === null) {
            return [
                'errors' => [ImportError::transformation("Could not determine record ID", $rowNumber)],
                'records' => []
            ];
        }

        // Process all field mappings for this row
        $recordsByCompositeKey = $this->processFieldMappings(
            $project,
            $mapping,
            $dataRow,
            $recordId,
            $dag,
            $errors
        );

        return [
            'errors' => $errors,
            'records' => array_values($recordsByCompositeKey)
        ];
    }

    /**
     * Process all field mappings for a single data row using FieldMapping objects
     *
     * @param array $errors Errors array (by reference)
     * @param string $recordId Record ID
     * @param ImportDataRow $dataRow Repositories row object
     * @return array Records grouped by composite key
     * @throws Exception
     */
    private function processFieldMappings(
        ProjectStructure $project,
        Mapping          $mapping,
        ImportDataRow    $dataRow,
        string           $recordId,
        ?string          $dag,
        array            &$errors
    ): array
    {
        $recordsByCompositeKey = [];

        foreach ($mapping->fieldMappings as $fieldMapping) {
            $result = $this->processSingleField(
                $project,
                $mapping,
                $fieldMapping,
                $dataRow,
                $recordId,
                $dag
            );

            if ($result->hasError) {
                $errors[] = $result->error;
                continue;
            }

            if ($result->isSkipped) {
                continue;
            }

            $compositeKey = $result->compositeKey;

            // Initialize record for this event/instance combination if one does not exist
            if (!isset($recordsByCompositeKey[$compositeKey])) {
                $recordsByCompositeKey[$compositeKey] = $result->record;
            }

            // Add field to the appropriate event/instance record
            $this->recordBuilder->addField(
                $recordsByCompositeKey[$compositeKey],
                $result->fieldName,
                $result->value
            );
        }

        return $recordsByCompositeKey;
    }

    /**
     * Process a single field mapping
     *
     * Handles event resolution, repeat instance matching, and record initialization
     * for a single field from the CSV data.
     *
     * @param string $recordId Record ID
     * @param ImportDataRow $dataRow Repositories row object
     * @param FieldMapping $fieldMapping Field mapping configuration
     * @return FieldProcessingResult Result object with success/error state
     * @throws Exception
     */
    private function processSingleField(
        ProjectStructure $project,
        Mapping          $mapping,
        FieldMapping     $fieldMapping,
        ImportDataRow    $dataRow,
        string           $recordId,
        ?string          $dag
    ): FieldProcessingResult
    {
        $csvFieldName = $fieldMapping->csvFieldName;
        $redcapFieldName = $fieldMapping->redcapFieldName;

        // Skip if source field missing
        if (!$dataRow->has($csvFieldName)) {
            return FieldProcessingResult::skipped();
        }

        $value = $dataRow->get($csvFieldName);

        // Field combination logic
        $combineFields = $fieldMapping->combineFields;
        if ($combineFields !== null && ($combineFields['enabled'] ?? false)) {
            $combinedValue = $this->fieldCombiner->combine($dataRow, $csvFieldName, $combineFields);
            if ($combinedValue !== null) {
                $value = $combinedValue;
            }
        }

        // Field regex transformation
        $value = $this->fieldRegexTransformer->transform($value, $fieldMapping->fieldRegex);

        // Date conversion logic
        $dateFormat = $fieldMapping->dateFormat;
        if ($dateFormat !== null && trim($value) !== '') {
            $convertedValue = $this->dateConverter->convert($value, $dateFormat);

            if ($convertedValue === null) {
                // Conversion failed
                $formatExample = match ($dateFormat) {
                    'MDY' => '01/15/2025 or 1/15/2025',
                    'DMY' => '15/01/2025 or 15/1/2025',
                    'YMD' => '2025/01/15',
                    default => 'valid date'
                };

                return FieldProcessingResult::error(
                    "Invalid date format for field '$csvFieldName'. Expected $dateFormat format (e.g., $formatExample)",
                    $dataRow->rowNumber,
                    $csvFieldName,
                    $value
                );
            }

            $value = $convertedValue;
        }

        // Value mapping logic
        $valueMappings = $fieldMapping->valueMappings;
        if (!empty($valueMappings)) {
            $value = $this->valueMapper->map($value, $valueMappings);
        }

        $formName = $fieldMapping->redcapFormName;
        $eventName = null;
        $isRepeatingEvent = false;
        if ($project->isLongitudinal) {
            // Determine event (for longitudinal projects)
            $eventName = $fieldMapping->redcapEventName;
            $isRepeatingEvent = $eventName !== null && $project->isRepeatingEvent($eventName);
        }
        $isRepeatingForm = !$isRepeatingEvent && $project->isRepeatingForm($formName, $eventName);

        $repeatInstance = null;
        $repeatInstrument = '';

        if ($isRepeatingEvent || $isRepeatingForm) {
            // New records have no existing instances — skip the getData() call
            if ($this->recordIdResolver->isNewRecord($recordId)) {
                $repeatInstance = 'new';
            } else {
                // Resolve matching field ID
                $matchingFieldId = $this->resolveMatchingField($project, $mapping, $fieldMapping);
                // Get the matching field name and value
                $matchingFieldMapping = null;
                $matchingValue = '';
                if ($matchingFieldId) {
                    $matchingFieldMapping = $mapping->findFieldMappingById($matchingFieldId);
                    if ($matchingFieldMapping) {
                        $matchingValue = $dataRow->get($matchingFieldMapping->csvFieldName) ?? '';
                    }
                }

                if ($matchingFieldMapping && $matchingValue !== '') {
                    $repeatInstance = $this->instanceResolver->resolve(
                        $recordId,
                        $eventName,
                        $matchingFieldMapping->redcapFieldName,
                        $matchingValue
                    );
                } else {
                    $repeatInstance = 'new';
                }
            }

            $repeatInstrument = $project->getRepeatInstrument($fieldMapping, $repeatInstance, $eventName);
        }

        // Create a unique composite key for this event/instance/instrument combination
        $compositeKey = $this->buildCompositeKey($eventName, $repeatInstance, $repeatInstrument);

        // Initialise record structure
        $record = $this->recordBuilder->initialize(
            $recordId,
            $project->recordIdFieldName,
            $eventName,
            $repeatInstance,
            $repeatInstrument,
            $project,
            $dag
        );

        return FieldProcessingResult::success($compositeKey, $record, $redcapFieldName, $value);
    }

    /**
     * Find the matching field ID for a given field mapping.
     *
     * @param FieldMapping $fieldMapping The field being processed
     * @return string|null Matching field ID or null if no matching configured
     */
    private function resolveMatchingField(
        ProjectStructure $project,
        Mapping          $mapping,
        FieldMapping     $fieldMapping
    ): ?string
    {
        $formName = $fieldMapping->redcapFormName;
        $eventName = $fieldMapping->redcapEventName;

        $cacheKey = $formName . '::' . ($eventName ?? '');
        if (array_key_exists($cacheKey, $this->resolveMatchingFieldCache)) {
            return $this->resolveMatchingFieldCache[$cacheKey];
        }

        $isLongitudinal = $project->isLongitudinal;
        $isRepeatingEvent = $isLongitudinal && $eventName !== null && $project->isRepeatingEvent($eventName);
        $isRepeatingForm = !$isRepeatingEvent && $project->isRepeatingForm($formName, $eventName);

        $result = null;

        // Check if instance matching is configured
        if ($isRepeatingEvent && $mapping->matchingConfig->eventEnabled) {
            foreach ($mapping->matchingConfig->eventFieldMappingIds as $fieldMappingId) {
                $candidateFieldMapping = $mapping->findFieldMappingById($fieldMappingId);
                if ($candidateFieldMapping && $candidateFieldMapping->redcapEventName === $eventName) {
                    $result = $fieldMappingId;
                    break;
                }
            }
        }

        if ($result === null && $isRepeatingForm && $mapping->matchingConfig->formEnabled) {
            foreach ($mapping->matchingConfig->formFieldMappingIds as $fieldMappingId) {
                $candidateFieldMapping = $mapping->findFieldMappingById($fieldMappingId);
                if (!$candidateFieldMapping) {
                    continue;
                }
                $formMatches = $candidateFieldMapping->redcapFormName === $formName;
                $eventMatches = !$isLongitudinal || $candidateFieldMapping->redcapEventName === $eventName;
                if ($formMatches && $eventMatches) {
                    $result = $fieldMappingId;
                    break;
                }
            }
        }

        $this->resolveMatchingFieldCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Build composite key for grouping records
     * Format: "eventId:repeatInstance:repeatInstrument"
     *
     * @param string|null $eventName Event name
     * @param int|string|null $repeatInstance Repeat instance
     * @param string $repeatInstrument Repeat instrument name
     * @return string Composite key
     */
    private function buildCompositeKey(?string $eventName, int|string|null $repeatInstance, string $repeatInstrument): string
    {
        return ($eventName ?? '') . '::' . ($repeatInstance ?? '') . '::' . $repeatInstrument;
    }
}
