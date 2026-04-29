<?php

namespace Nottingham\ImportMapper\Services;

use Exception;
use Nottingham\ImportMapper\Models\ImportError;
use Nottingham\ImportMapper\Models\ImportResult;
use Nottingham\ImportMapper\Models\Mapping;
use Nottingham\ImportMapper\Repositories\RecordRepository;
use Nottingham\ImportMapper\Services\Validation\CsvValidator;


/**
 * Orchestrates imports
 */
final readonly class ImportService
{
    /**
     * Constructor
     *
     * @param ProjectService $projectService
     * @param RecordRepository $recordRepository
     * @param CsvValidator $csvValidator
     * @param CsvParser $csvParser CSV parser
     * @param RowProcessor $rowProcessor
     */
    public function __construct(
        private ProjectService   $projectService,
        private RecordRepository $recordRepository,
        private CsvValidator     $csvValidator,
        private CsvParser        $csvParser,
        private RowProcessor     $rowProcessor
    )
    {
    }

    /**
     * Process CSV import
     *
     * Processes a chunk of CSV rows, saving each row to REDCap individually.
     * If $csvOffset > 0, skips the first $csvOffset rows (resume after a previous chunk).
     *
     * @param Mapping $mapping The mapping configuration
     * @param string $csvFilePath Path to the CSV file on disk
     * @param callable|null $tickCallback Optional callback (string $phase, int $rowsProcessed, int $totalRows)
     * @param int|null $projectId When non-null, loads project by ID
     * @param int $csvOffset Number of data rows to skip (for chunked resume)
     * @param int $chunkSize Maximum number of rows to process in this call
     * @param int|null $totalRows Known total rows from previous chunk; skips re-counting when provided
     * @return ImportResult The import result
     * @throws Exception
     */
    public function import(
        Mapping   $mapping,
        string    $csvFilePath,
        ?callable $tickCallback = null,
        ?int      $projectId = null,
        int       $csvOffset = 0,
        int       $chunkSize = 500,
        ?int      $totalRows = null
    ): ImportResult
    {
        try {
            $projectStructure = $this->projectService->get($projectId);

            // Validate DAG configuration
            $dagConfig = $mapping->matchingConfig->dagConfig;
            if ($dagConfig->enabled) {
                $dagErrors = $dagConfig->validate(
                    $mapping->csvFields,
                    array_keys($projectStructure->dataAccessGroups)
                );
                if (!empty($dagErrors)) {
                    return ImportResult::fromErrors($dagErrors);
                }
            }

            // Parse only the current chunk directly from the file
            $csvData = $this->csvParser->parseFile($csvFilePath, $csvOffset, $chunkSize, $totalRows);
            $totalRows = $csvData->totalRows;

            // Validate CSV structure
            $csvValidation = $this->csvValidator->validate($csvData, $mapping);
            if (!$csvValidation->isValid) {
                return ImportResult::fromErrors($csvValidation->errors);
            }
            if ($tickCallback !== null) {
                $tickCallback('validating', $csvOffset, $totalRows);
            }

            // Pre-fetch record and instance matching lookups before the row loop
            $this->rowProcessor->prefetch($mapping, $projectStructure, $projectId, $csvData);

            // Process each row, saving to REDCap individually
            $errors = [];
            $warnings = [];
            $recordIds = [];
            $itemsUpdated = 0;
            $rowsProcessed = 0;

            foreach ($csvData->rows as $dataRow) {
                // call tick: to deal with state/behaviour that needs to run regularly during import
                if ($tickCallback !== null && $tickCallback('transforming', $csvOffset + $rowsProcessed, $totalRows)) {
                    return ImportResult::cancelled($errors, $warnings);
                }

                // Skip empty rows
                if ($dataRow->isEmpty()) {
                    $rowsProcessed++;
                    continue;
                }

                // Record invalid rows as csvData errors and continue
                if (!$dataRow->valid) {
                    $errors[] = ImportError::csvData($dataRow->invalidReason, $dataRow->rowNumber);
                    $rowsProcessed++;
                    continue;
                }

                // Transform row to REDCap record format
                $result = $this->rowProcessor->process($projectStructure, $mapping, $dataRow, $projectId);

                // Collect any field-level errors (errored fields are skipped, other fields in row still saved)
                if (!empty($result['errors'])) {
                    array_push($errors, ...$result['errors']);
                }

                if (!empty($result['records'])) {
                    // Save this row's records to REDCap immediately
                    $saveResponse = $this->recordRepository->save($result['records'], $projectId);

                    // Extract errors, attributing them to this row
                    $rawErrors = $saveResponse['errors'] ?? [];
                    if (!is_array($rawErrors)) {
                        $rawErrors = $rawErrors ? [$rawErrors] : [];
                    }
                    foreach ($rawErrors as $msg) {
                        $errors[] = ImportError::redcapSave((string)$msg, $dataRow->rowNumber);
                    }

                    // Accumulate successes
                    $rowWarnings = $saveResponse['warnings'] ?? [];
                    if (!is_array($rowWarnings)) {
                        $rowWarnings = $rowWarnings ? [$rowWarnings] : [];
                    }
                    array_push($warnings, ...$rowWarnings);

                    $rowIds = array_values($saveResponse['ids'] ?? []);
                    array_push($recordIds, ...$rowIds);

                    $itemsUpdated += (int)($saveResponse['item_count'] ?? 0);
                }

                $rowsProcessed++;
            }
            // check if this is the last chunk
            $isLastChunk = ($csvOffset + $rowsProcessed) >= $totalRows;

            return new ImportResult(
                success: empty($errors),
                recordIds: array_values(array_unique($recordIds)),
                errors: $errors,
                rowsProcessed: $rowsProcessed,
                itemsUpdated: $itemsUpdated,
                warnings: $warnings,
                totalRows: $totalRows,
                isLastChunk: $isLastChunk,
            );
        } catch (Exception $e) {
            return ImportResult::fromErrors([ImportError::transformation($e->getMessage(), 0)]);
        }
    }
}
