<?php

namespace Nottingham\ImportMapper\Services;

use Exception;
use Nottingham\ImportMapper\Models\FieldMapping;
use Nottingham\ImportMapper\Models\ProjectStructure;
use Nottingham\ImportMapper\Repositories\RecordRepository;
use Nottingham\ImportMapper\Models\ImportDataRow;

final class RecordIdResolver
{
    private readonly RecordRepository $recordRepository;
    private array $recordMatchingCache = [];
    private bool $prefetchDone = false;
    // Record IDs created during this import
    private array $newRecordIds = [];

    /**
     * @param RecordRepository $recordRepository
     */
    public function __construct(RecordRepository $recordRepository)
    {
        $this->recordRepository = $recordRepository;
    }

    /**
     * Pre-populate the record matching cache with all existing value-recordId pairs.
     *
     * @param string $fieldName REDCap field to match on
     * @param string|null $eventName Event name for longitudinal projects
     * @param int|null $projectId Project ID
     * @param array $filterValues Unique CSV values to filter by
     * @throws Exception
     */
    public function prefetch(string $fieldName, ?string $eventName, ?int $projectId, array $filterValues = []): void
    {
        $allPairs = $this->recordRepository->fetchAllMatchingRecordIds($fieldName, $eventName, $projectId, $filterValues);

        foreach ($allPairs as $value => $recordId) {
            $cacheKey = $fieldName . '::' . $value . '::' . ($eventName ?? '');
            $this->recordMatchingCache[$cacheKey] = $recordId;
        }

        $this->prefetchDone = true;
    }

    /**
     * Check whether a record ID was newly created during this import.
     */
    public function isNewRecord(string $recordId): bool
    {
        return isset($this->newRecordIds[$recordId]);
    }

    /**
     * Return all record IDs that were matched to existing records during prefetch.
     *
     * @return string[] Unique existing record IDs
     */
    public function getExistingRecordIds(): array
    {
        return array_values(array_unique(array_values($this->recordMatchingCache)));
    }

    /**
     * Resolve record ID for a data row
     *
     * @param ImportDataRow $dataRow Repositories row object
     * @param FieldMapping|null $recordMatchingFieldMapping Field mapping for record matching (if enabled)
     * @param int|null $projectId Project ID (required in cron context)
     * @param ProjectStructure|null $project ProjectStructure model
     * @param string|null $dag DAG unique name for the current row
     * @return string|null Record ID or null if it cannot be determined
     * @throws Exception
     */
    public function resolve(
        ImportDataRow     $dataRow,
        ?FieldMapping     $recordMatchingFieldMapping = null,
        ?int              $projectId = null,
        ?ProjectStructure $project = null,
        ?string           $dag = null
    ): ?string
    {
        // If no matching config, reserve a new record ID
        if ($recordMatchingFieldMapping === null) {
            $newId = $this->recordRepository->reserveNewRecordId($projectId, $project, $dag, null);
            $this->newRecordIds[$newId] = true;
            return $newId;
        }

        $csvFieldName = $recordMatchingFieldMapping->csvFieldName;
        $redcapFieldName = $recordMatchingFieldMapping->redcapFieldName;
        $eventName = $recordMatchingFieldMapping->redcapEventName;
        $matchingValue = $csvFieldName ? $dataRow->get($csvFieldName) : null;

        // If there is no matching value, reserve a new record ID
        if (empty($matchingValue)) {
            $newId =
                $this->recordRepository->reserveNewRecordId($projectId, $project, $dag, $eventName);
            $this->newRecordIds[$newId] = true;
            return $newId;
        }

        $cacheKey = $redcapFieldName . '::' . $matchingValue . '::' . ($eventName ?? '');
        if (isset($this->recordMatchingCache[$cacheKey])) {
            return $this->recordMatchingCache[$cacheKey];
        }

        if ($this->prefetchDone) {
            // Prefetch loaded all existing records; a cache miss means this value is new
            $newId =
                $this->recordRepository->reserveNewRecordId($projectId, $project, $dag, $eventName);
            $this->newRecordIds[$newId] = true;
            
            // if new id add to cache so does not create duplicates if more than one matching value in file
            $this->recordMatchingCache[$cacheKey] = $newId;
            return $newId;
        }

        // Should not be reached: prefetch always runs when record matching is enabled
        return null;
    }
}
