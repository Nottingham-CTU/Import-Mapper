<?php

namespace Nottingham\ImportMapper\Controllers;

use Throwable;
use Nottingham\ImportMapper\Models\ImportError;
use Nottingham\ImportMapper\Models\ImportErrorOrigin;
use Nottingham\ImportMapper\Models\ImportLogEntry;
use Nottingham\ImportMapper\Repositories\MappingRepository;
use Nottingham\ImportMapper\Services\ImportService;
use Nottingham\ImportMapper\Services\ProjectService;

final readonly class ImportController
{
    /**
     * Constructor
     *
     * @param ImportService $importService
     * @param MappingRepository $mappingRepository
     * @param ProjectService $projectService
     */
    public function __construct(
        private ImportService     $importService,
        private MappingRepository $mappingRepository,
        private ProjectService    $projectService
    )
    {
    }

    /**
     * Process a queued import job (or one chunk of it).
     *
     * @param array $job Job data: mappingId, mappingName, csvPath
     * @param int $projectId The project ID
     * @param callable|null $tickCallback Optional callback
     * @param int $csvOffset Number of data rows already processed (for chunked resume)
     * @param int $chunkSize Maximum number of rows to process in this call
     * @param int|null $totalRows Known total rows from previous chunk; avoids re-counting the entire file
     * @return array ['success' => bool, 'logEntry' => ImportLogEntry, 'warnings' => array, 'errors' => array, 'isLastChunk' => bool] | ['cancelled' => true]
     */
    public function processJob(
        array     $job,
        int       $projectId,
        ?callable $tickCallback = null,
        int       $csvOffset = 0,
        int       $chunkSize = 500,
        ?int      $totalRows = null
    ): array
    {
        try {
            $mapping = $this->mappingRepository->findById($job['mappingId'], $projectId);

            // Only validate mapping status and project hash on the first chunk
            if ($csvOffset === 0) {
                if ($mapping->status !== 'final') {
                    return [
                        'success'     => false,
                        'failed'      => true,
                        'isLastChunk' => true,
                        'logEntry'    => new ImportLogEntry(
                            mappingId: $job['mappingId'],
                            mappingName: $job['mappingName'],
                            status: 'failed',
                            outcome: 'errors',
                            mappingErrorCount: 1,
                        ),
                        'recordIds' => [],
                        'warnings'  => [],
                        'errors'    => [ImportError::mapping("Mapping '{$job['mappingName']}' is not in final status and cannot be used for imports.")],
                    ];
                }

                $currentHash = $this->projectService->getProjectStructureHash($projectId);
                if ($mapping->projectStructureHash !== $currentHash) {
                    return [
                        'success'     => false,
                        'failed'      => true,
                        'isLastChunk' => true,
                        'logEntry'    => new ImportLogEntry(
                            mappingId: $job['mappingId'],
                            mappingName: $job['mappingName'],
                            status: 'failed',
                            outcome: 'errors',
                            mappingErrorCount: 1,
                        ),
                        'recordIds' => [],
                        'warnings'  => [],
                        'errors'    => [ImportError::mapping('The project structure has changed since this mapping was last saved. Please review and re-save the mapping before importing.')],
                    ];
                }
            }

            if (!file_exists($job['csvPath'])) {
                return [
                    'success'     => false,
                    'failed'      => true,
                    'isLastChunk' => true,
                    'logEntry'    => new ImportLogEntry(
                        mappingId: $job['mappingId'],
                        mappingName: $job['mappingName'],
                        status: 'failed',
                        outcome: 'errors',
                        mappingErrorCount: 1,
                    ),
                    'recordIds' => [],
                    'warnings'  => [],
                    'errors'    => [ImportError::mapping('Could not read the temporary import file.')],
                ];
            }

            $result = $this->importService->import(
                $mapping,
                $job['csvPath'],
                $tickCallback,
                $projectId,
                $csvOffset,
                $chunkSize,
                $totalRows
            );

            if ($result->cancelled) {
                $counts = [];
                foreach (ImportErrorOrigin::cases() as $origin) {
                    $counts[$origin->value] = count(array_filter(
                        $result->errors,
                        fn(ImportError $e) => $e->origin === $origin
                    ));
                }
                return [
                    'cancelled' => true,
                    'errors'    => $result->errors,
                    'warnings'  => $result->warnings,
                    'logEntry'  => new ImportLogEntry(
                        mappingId:               $mapping->id,
                        mappingName:             $mapping->name,
                        status:                  'cancelled',
                        mappingErrorCount:       $counts['MAPPING'],
                        csvStructureErrorCount:  $counts['CSV_STRUCTURE'],
                        csvDataErrorCount:       $counts['CSV_DATA'],
                        transformationErrorCount: $counts['TRANSFORMATION'],
                        redcapSaveErrorCount:    $counts['REDCAP_SAVE'],
                        warningCount:            count($result->warnings),
                    ),
                ];
            }

            $counts = [];
            foreach (ImportErrorOrigin::cases() as $origin) {
                $counts[$origin->value] = count(array_filter(
                    $result->errors,
                    fn(ImportError $e) => $e->origin === $origin
                ));
            }

            $warningCount = count($result->warnings);

            $isInterrupted = $counts['MAPPING'] > 0 || $counts['CSV_STRUCTURE'] > 0 || !$result->success;
            $chunkStatus = $isInterrupted ? 'failed' : 'completed';
            $chunkOutcome = $isInterrupted ? 'errors' : match (true) {
                $counts['CSV_DATA'] > 0 || $counts['TRANSFORMATION'] > 0 || $counts['REDCAP_SAVE'] > 0 => 'errors',
                $warningCount > 0 => 'warnings',
                default => 'clean',
            };

            $logEntry = new ImportLogEntry(
                mappingId: $mapping->id,
                mappingName: $mapping->name,
                status: $chunkStatus,
                outcome: $chunkOutcome,
                rowsProcessed: $csvOffset + $result->rowsProcessed,
                itemsUpdated: $result->itemsUpdated,
                recordsUpdated: count($result->recordIds),
                totalRows: $result->totalRows,
                mappingErrorCount: $counts['MAPPING'],
                csvStructureErrorCount: $counts['CSV_STRUCTURE'],
                csvDataErrorCount: $counts['CSV_DATA'],
                transformationErrorCount: $counts['TRANSFORMATION'],
                redcapSaveErrorCount: $counts['REDCAP_SAVE'],
                warningCount: $warningCount,
            );

            return [
                'success'     => $result->success,
                'failed'      => $result->failed,
                'isLastChunk' => $result->isLastChunk,
                'logEntry'    => $logEntry,
                'recordIds'   => $result->recordIds,
                'warnings'    => $result->warnings,
                'errors'      => $result->errors,
            ];
        } catch (Throwable $e) {
            return [
                'success'     => false,
                'failed'      => true,
                'isLastChunk' => true,
                'logEntry' => new ImportLogEntry(
                    mappingId: $job['mappingId'],
                    mappingName: $job['mappingName'],
                    status: 'failed',
                    outcome: 'errors',
                    mappingErrorCount: 1,
                ),
                'recordIds' => [],
                'warnings' => [],
                'errors' => [ImportError::mapping($e->getMessage())],
            ];
        }
    }
}
