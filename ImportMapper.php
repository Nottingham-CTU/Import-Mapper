<?php

namespace Nottingham\ImportMapper;

require_once __DIR__ . '/vendor/autoload.php';

use ExternalModules\AbstractExternalModule;
use Nottingham\ImportMapper\Controllers\ImportController;
use Nottingham\ImportMapper\Models\ImportError;
use Nottingham\ImportMapper\Models\ImportLogEntry;
use Nottingham\ImportMapper\Models\ProjectStructure;
use Nottingham\ImportMapper\Repositories\ImportLogRepository;
use Nottingham\ImportMapper\Repositories\MappingRepository;
use Nottingham\ImportMapper\Repositories\RecordRepository;
use Nottingham\ImportMapper\Services\CsvParser;
use Nottingham\ImportMapper\Services\Validation\CsvValidator;
use Nottingham\ImportMapper\Services\DagResolver;
use Nottingham\ImportMapper\Services\DateConverter;
use Nottingham\ImportMapper\Services\FieldCombiner;
use Nottingham\ImportMapper\Services\FieldRegexTransformer;
use Nottingham\ImportMapper\Services\ImportJobManager;
use Nottingham\ImportMapper\Services\ImportJobQueue;
use Nottingham\ImportMapper\Services\ImportNotifier;
use Nottingham\ImportMapper\Services\ImportRunState;
use Nottingham\ImportMapper\Services\Validation\MappingValidator;
use Nottingham\ImportMapper\Services\RecordBuilder;
use Nottingham\ImportMapper\Services\RecordIdResolver;
use Nottingham\ImportMapper\Controllers\ProjectController;
use Nottingham\ImportMapper\Controllers\MappingController;
use Nottingham\ImportMapper\Controllers\ImportLogController;
use Nottingham\ImportMapper\Controllers\CsvController;
use Nottingham\ImportMapper\Controllers\RegexController;
use Nottingham\ImportMapper\Services\ImportService;
use Nottingham\ImportMapper\Services\InstanceResolver;
use Nottingham\ImportMapper\Services\ProjectService;
use Nottingham\ImportMapper\Services\RowProcessor;
use Nottingham\ImportMapper\Services\PermissionService;
use Nottingham\ImportMapper\Services\Validation\FieldMappingStructureValidator;
use Nottingham\ImportMapper\Services\Validation\FieldMappingTransformValidator;
use Nottingham\ImportMapper\Services\ValueMapper;

class ImportMapper extends AbstractExternalModule
{
    public ?PermissionService $permissionService = null {
        get {
            if ($this->permissionService === null) {
                $this->permissionService = new PermissionService($this);
            }
            return $this->permissionService;
        }
    }
    private ?ProjectService $projectService = null {
        get {
            if ($this->projectService === null) {
                $this->projectService = new ProjectService($this);
            }
            return $this->projectService;
        }
    }
    private ?RecordRepository $recordRepository = null {
        get {
            if ($this->recordRepository === null) {
                $this->recordRepository = new RecordRepository($this, $this->projectService);
            }
            return $this->recordRepository;
        }
    }
    private ?MappingRepository $mappingRepository = null {
        get {
            if ($this->mappingRepository === null) {
                $this->mappingRepository = new MappingRepository($this);
            }
            return $this->mappingRepository;
        }
    }
    private ?ImportLogRepository $importLogRepository = null {
        get {
            if ($this->importLogRepository === null) {
                $this->importLogRepository = new ImportLogRepository($this);
            }
            return $this->importLogRepository;
        }
    }
    private ?ImportRunState $importRunState = null {
        get {
            if ($this->importRunState === null) {
                $this->importRunState = new ImportRunState($this);
            }
            return $this->importRunState;
        }
    }
    private ?ImportJobQueue $importJobQueue = null {
        get {
            if ($this->importJobQueue === null) {
                $this->importJobQueue = new ImportJobQueue($this);
            }
            return $this->importJobQueue;
        }
    }
    public ?ImportJobManager $importJobManager = null {
        get {
            if ($this->importJobManager === null) {
                $this->importJobManager = new ImportJobManager(
                    $this,
                    $this->importJobQueue,
                    $this->importRunState,
                    $this->importLogRepository,
                    $this->mappingRepository,
                );
            }
            return $this->importJobManager;
        }
    }
    private ?ImportNotifier $importNotifier = null {
        get {
            if ($this->importNotifier === null) {
                $this->importNotifier = new ImportNotifier();
            }
            return $this->importNotifier;
        }
    }
    private ?ProjectController $projectController = null {
        get {
            if ($this->projectController === null) {
                $this->projectController = new ProjectController($this->projectService, $this->permissionService);
            }
            return $this->projectController;
        }
    }
    private ?MappingController $mappingController = null {
        get {
            if ($this->mappingController === null) {
                $this->mappingController = new MappingController(
                    $this->mappingRepository,
                    $this->permissionService,
                    $this->projectService,
                    new MappingValidator(
                        new FieldMappingStructureValidator(),
                        new FieldMappingTransformValidator(),
                        $this->projectService,
                    ),
                    $this->importJobManager,
                );
            }
            return $this->mappingController;
        }
    }
    private ?ImportController $importController = null {
        get {
            if ($this->importController === null) {
                $this->importController = new ImportController(
                    $this->importService,
                    $this->mappingRepository,
                    $this->projectService
                );
            }
            return $this->importController;
        }
    }
    private ?ImportLogController $importLogController = null {
        get {
            if ($this->importLogController === null) {
                $this->importLogController = new ImportLogController(
                    $this->importLogRepository,
                    $this->permissionService,
                    $this->importJobManager,
                );
            }
            return $this->importLogController;
        }
    }
    private ?CsvController $csvController = null {
        get {
            if ($this->csvController === null) {
                $this->csvController = new CsvController(
                    new CsvParser(),
                    $this->permissionService
                );
            }
            return $this->csvController;
        }
    }
    private ?RegexController $regexController = null {
        get {
            if ($this->regexController === null) {
                $this->regexController = new RegexController();
            }
            return $this->regexController;
        }
    }
    private ?RecordIdResolver $recordIdResolver = null;
    private ?ImportService $importService = null {
        get {
            if ($this->importService === null) {
                $recordRepository = $this->recordRepository;

                $this->recordIdResolver = new RecordIdResolver($recordRepository);

                $rowProcessor = new RowProcessor(
                    $this->recordIdResolver,
                    new RecordBuilder(),
                    new InstanceResolver($recordRepository),
                    new DagResolver(),
                    new DateConverter(),
                    new ValueMapper(),
                    new FieldCombiner(
                        new FieldRegexTransformer()
                    ),
                    new FieldRegexTransformer()
                );

                // Create import service
                $this->importService = new ImportService(
                    $this->projectService,
                    $recordRepository,
                    new CsvValidator(),
                    new CsvParser(),
                    $rowProcessor
                );
            }
            return $this->importService;
        }
    }

    /**
     * Cron method — processes all queued import jobs, one chunk per job per cron run.
     *
     * Each job is processed in chunks of 500 rows. If a chunk completes but there are more rows,
     * the run state is persisted in a project setting so the next cron run resumes where it left off.
     * The job remains in the job index until all chunks are complete.
     *
     * @param array $cronAttributes
     */
    public function processImportJobs(array $cronAttributes): void
    {
        // Global lock: only one import job may run at a time across all projects
        // this is for performance reasons
        $jobIndex = $this->importJobQueue->getIndex();
        $activeLockJson = $this->getSystemSetting('import_active_job');
        $activeLock = $activeLockJson ? json_decode($activeLockJson, true) : null;

        if ($activeLock !== null) {
            $lockedJobId = $activeLock['jobId'] ?? null;
            $heartbeatAt = $activeLock['heartbeatAt'] ?? null;
            $lockedAt = $activeLock['lockedAt'] ?? 0;
            $staleThreshold = 300; // 5 minutes

            // heartbeatAt is updated as the job runs to signify progress is being made
            // e.g. it hasn't crashed or otherwise got stuck
            if ($heartbeatAt !== null) {
                $isStale = (time() - $heartbeatAt) > $staleThreshold;
            } else {
                $isStale = (time() - $lockedAt) > $staleThreshold;
            }

            // isStale signifies no progress has been made on the job for 5 minutes
            if ($isStale) {
                // remove the lock
                $this->removeSystemSetting('import_active_job');
            } elseif ($lockedJobId !== ($jobIndex[0] ?? null)) {
                // A different job holds the lock — nothing to do this cron run
                return;
            } elseif ($heartbeatAt !== null) {
                // Same job holds the lock and has an active heartbeat — it is currently running.
                // Do not start a second concurrent runner for the same chunk.
                return;
            }
            // else: same job holds the lock but no heartbeat — chunk completed, waiting between chunks;
            // start the next chunk
        }

        // see which jobs are in the queue and remove orphaned jobs
        foreach ($jobIndex as $jobId) {
            $job = $this->importJobQueue->getJob($jobId);
            if ($job === null) {
                // job is orphaned — clean up
                $this->importJobQueue->remove($jobId);
                continue;
            }

            $projectId = (int)$job['projectId'];
            // check the current status of this import job
            $runState = $this->importRunState->get($jobId, $projectId);
            $isFirstChunk = ($runState === null || ($runState['csvOffset'] ?? 0) === 0);

            // Acquire lock for this job
            $this->setSystemSetting('import_active_job', json_encode([
                'jobId' => $jobId,
                'lockedAt' => time(),
            ]));

            // Handle cancel clicked between chunks
            if (($runState['status'] ?? '') === 'cancelling') {
                // log the job and send email notification
                $cancelledEntry = new ImportLogEntry(
                    mappingId: $job['mappingId'],
                    mappingName: $job['mappingName'],
                    status: 'cancelled',
                    rowsProcessed: $runState['rowsProcessed'] ?? 0,
                    itemsUpdated: $runState['itemsUpdated'] ?? 0,
                    recordsUpdated: count(array_unique($runState['recordIds'] ?? [])),
                    totalRows: $runState['totalRows'] ?? 0,
                    mappingErrorCount: $runState['mappingErrorCount'] ?? 0,
                    csvStructureErrorCount: $runState['csvStructureErrorCount'] ?? 0,
                    csvDataErrorCount: $runState['csvDataErrorCount'] ?? 0,
                    transformationErrorCount: $runState['transformationErrorCount'] ?? 0,
                    redcapSaveErrorCount: $runState['redcapSaveErrorCount'] ?? 0,
                    warningCount: $runState['warningCount'] ?? 0,
                    username: $runState['username'] ?? '',
                    jobId: $jobId,
                    queuedAt: $runState['queuedAt'] ?? null,
                    startedAt: $runState['startedAt'] ?? null,
                );
                $this->importLogRepository->save($cancelledEntry, $projectId);
                $notificationEmails = $job['notificationEmails'] ?? [];
                if (!empty($notificationEmails)) {
                    $accumulatedErrors = array_map(
                        fn(array $e) => ImportError::fromArray($e),
                        $runState['errors'] ?? []
                    );
                    $this->importNotifier->notify(
                        $notificationEmails,
                        $cancelledEntry,
                        $accumulatedErrors,
                        $runState['warnings'] ?? [],
                        $projectId
                    );
                }
                // delete the project level job run state (holds progress of the job)
                $this->importRunState->clear($jobId, $projectId);
                // delete the temp file
                @unlink($job['csvPath']);
                // remove the system level job metadata (holds project id etc.)
                $this->removeSystemSetting("import_job_$jobId");
                // remove the job from the job queue
                $this->importJobQueue->remove($jobId);
                // remove the lock
                $this->removeSystemSetting('import_active_job');
                break;
            }

            // Clear any previous chunkCompletedAt so frontend knows a chunk is actively running
            unset($runState['chunkCompletedAt']);

            // Restore cached ProjectStructure from run state on chunks 2+
            if (!$isFirstChunk && isset($runState['projectStructure'])) {
                $cachedStructure = ProjectStructure::fromSerializable($runState['projectStructure']);
                $this->projectService->setCache($projectId, $cachedStructure);
            }

            if ($isFirstChunk) {
                // Transition to in_progress status
                $runState['status'] = 'in_progress';
                $runState['startedAt'] = date('c');
            }

            $this->importRunState->save($jobId, $projectId, $runState);

            // Track last-known progress for possible cancel log entry
            $lastRowsProcessed = $runState['rowsProcessed'] ?? 0;
            $lastTotalRows = $runState['totalRows'] ?? 0;

            // tick callback: captures state/behaviour that must happen regularly during import
            // only called every 10 rows for performance reasons
            $throttleRowCounter = 0;
            $tickCallback = function (string $phase, int $rowsProcessed, int $totalRows)
            use (&$runState, &$lastRowsProcessed, &$lastTotalRows, &$throttleRowCounter, $jobId, $projectId): bool {
                $lastRowsProcessed = $rowsProcessed;
                $lastTotalRows = $totalRows;
                $throttleRowCounter++;
                if ($throttleRowCounter < 10) {
                    return false;
                }
                $throttleRowCounter = 0;
                // get the current run state and update it with progress so far
                $current = $this->importRunState->get($jobId, $projectId) ?? $runState;
                $current['rowsProcessed'] = $rowsProcessed;
                $current['totalRows'] = $totalRows;
                unset($current['chunkCompletedAt']);
                $runState = $current;
                $this->importRunState->save($jobId, $projectId, $runState);
                // Refresh global lock heartbeat
                $currentLock = json_decode($this->getSystemSetting('import_active_job') ?? '{}', true);
                $currentLock['heartbeatAt'] = time();
                $this->setSystemSetting('import_active_job', json_encode($currentLock));
                return ($runState['status'] ?? '') === 'cancelling';
            };

            // Pass known total rows on chunks 2+ to avoid re-counting the entire file
            $knownTotalRows = (!$isFirstChunk && ($runState['totalRows'] ?? 0) > 0)
                ? (int)$runState['totalRows']
                : null;

            // Process one chunk
            $result = $this->importController->processJob(
                $job,
                $projectId,
                $tickCallback,
                $runState['csvOffset'] ?? 0,
                500,
                $knownTotalRows
            );

            // check again for user cancellation
            if ($result['cancelled'] ?? false) {
                $cancelledEntry = new ImportLogEntry(
                    mappingId: $job['mappingId'],
                    mappingName: $job['mappingName'],
                    status: 'cancelled',
                    rowsProcessed: $lastRowsProcessed,
                    itemsUpdated: $runState['itemsUpdated'] ?? 0,
                    recordsUpdated: count(array_unique($runState['recordIds'] ?? [])),
                    totalRows: $lastTotalRows,
                    mappingErrorCount: ($runState['mappingErrorCount'] ?? 0) + $result['logEntry']->mappingErrorCount,
                    csvStructureErrorCount: ($runState['csvStructureErrorCount'] ?? 0) + $result['logEntry']->csvStructureErrorCount,
                    csvDataErrorCount: ($runState['csvDataErrorCount'] ?? 0) + $result['logEntry']->csvDataErrorCount,
                    transformationErrorCount: ($runState['transformationErrorCount'] ?? 0) + $result['logEntry']->transformationErrorCount,
                    redcapSaveErrorCount: ($runState['redcapSaveErrorCount'] ?? 0) + $result['logEntry']->redcapSaveErrorCount,
                    warningCount: ($runState['warningCount'] ?? 0) + $result['logEntry']->warningCount,
                    username: $runState['username'] ?? '',
                    jobId: $jobId,
                    queuedAt: $runState['queuedAt'] ?? null,
                    startedAt: $runState['startedAt'] ?? null,
                );
                $this->terminateJob($cancelledEntry, $job, $runState, $jobId, $projectId, $result);
                break;
            }

            // Accumulate counts from this chunk into run state.
            // $chunkEntry->rowsProcessed = $csvOffset + rows processed in this chunk (cumulative).
            $chunkEntry = $result['logEntry'];
            $runState['rowsProcessed'] = $chunkEntry->rowsProcessed;
            $runState['itemsUpdated'] = ($runState['itemsUpdated'] ?? 0) + $chunkEntry->itemsUpdated;
            $runState['recordIds'] = array_values(array_unique(
                array_merge($runState['recordIds'] ?? [], $result['recordIds'] ?? [])
            ));
            $runState['totalRows'] = $chunkEntry->totalRows ?: $runState['totalRows'];
            $runState['mappingErrorCount'] = ($runState['mappingErrorCount'] ?? 0) + $chunkEntry->mappingErrorCount;
            $runState['csvStructureErrorCount'] = ($runState['csvStructureErrorCount'] ?? 0) + $chunkEntry->csvStructureErrorCount;
            $runState['csvDataErrorCount'] = ($runState['csvDataErrorCount'] ?? 0) + $chunkEntry->csvDataErrorCount;
            $runState['transformationErrorCount'] = ($runState['transformationErrorCount'] ?? 0) + $chunkEntry->transformationErrorCount;
            $runState['redcapSaveErrorCount'] = ($runState['redcapSaveErrorCount'] ?? 0) + $chunkEntry->redcapSaveErrorCount;
            $runState['warningCount'] = ($runState['warningCount'] ?? 0) + $chunkEntry->warningCount;
            $runState['errors'] = array_merge(
                $runState['errors'] ?? [],
                array_map(fn(ImportError $e) => $e->toArray(), $result['errors'] ?? [])
            );
            $runState['warnings'] = array_merge($runState['warnings'] ?? [], $result['warnings'] ?? []);

            // if this isn't the last chunk, prep the run state for the start of the next chunk
            if (!$result['isLastChunk'] && !($result['failed'] ?? false)) {
                // More rows to process — advance offset and persist run state for next cron run
                $runState['csvOffset'] = $chunkEntry->rowsProcessed;
                $runState['chunkCompletedAt'] = time();

                // Cache ProjectStructure so chunks 2+ skip the full Project constructor load
                if (!isset($runState['projectStructure'])) {
                    $runState['projectStructure'] = $this->projectService->get($projectId)->toSerializable();
                }

                $this->importRunState->save($jobId, $projectId, $runState);

                // Refresh lock for between-chunk wait (clear heartbeat so stale check uses lockedAt)
                $this->setSystemSetting('import_active_job', json_encode([
                    'jobId' => $jobId,
                    'lockedAt' => time(),
                    // heartbeatAt cleared — next chunk hasn't started yet
                ]));

                // Only one job should per cron run; break so no other job starts this invocation.
                break;
            }

            // Final chunk (or job failure) — write final log entry
            $isInterrupted = $runState['mappingErrorCount'] > 0
                || $runState['csvStructureErrorCount'] > 0
                || ($result['failed'] ?? false);
            $hasRowErrors = $runState['csvDataErrorCount'] > 0
                || $runState['transformationErrorCount'] > 0
                || $runState['redcapSaveErrorCount'] > 0;

            $finalStatus = $isInterrupted ? 'failed' : 'completed';
            $finalOutcome = $isInterrupted ? 'errors'
                : ($hasRowErrors ? 'errors'
                    : ($runState['warningCount'] > 0 ? 'warnings' : 'clean'));

            $finalEntry = new ImportLogEntry(
                mappingId: $job['mappingId'],
                mappingName: $job['mappingName'],
                status: $finalStatus,
                outcome: $finalOutcome,
                rowsProcessed: $runState['rowsProcessed'],
                itemsUpdated: $runState['itemsUpdated'] ?? 0,
                recordsUpdated: count(array_unique($runState['recordIds'])),
                totalRows: $runState['totalRows'],
                mappingErrorCount: $runState['mappingErrorCount'],
                csvStructureErrorCount: $runState['csvStructureErrorCount'],
                csvDataErrorCount: $runState['csvDataErrorCount'],
                transformationErrorCount: $runState['transformationErrorCount'],
                redcapSaveErrorCount: $runState['redcapSaveErrorCount'],
                warningCount: $runState['warningCount'],
                username: $runState['username'] ?? '',
                jobId: $jobId,
                queuedAt: $runState['queuedAt'] ?? null,
                startedAt: $runState['startedAt'] ?? null,
            );

            $this->terminateJob($finalEntry, $job, $runState, $jobId, $projectId, $result);
            break;
        }
        //remove very old jobs from queue
        $this->importJobQueue->cleanupOrphaned();
    }

    private function terminateJob(ImportLogEntry $entry, array $job, array $runState, string $jobId, int $projectId, array $lastChunkResult): void
    {
        // save log entry and send notification emails
        $this->importLogRepository->save($entry, $projectId);
        $notificationEmails = $job['notificationEmails'] ?? [];
        if (!empty($notificationEmails)) {
            $accumulatedErrors = array_map(
                fn(array $e) => ImportError::fromArray($e),
                $runState['errors'] ?? []
            );
            $this->importNotifier->notify(
                $notificationEmails,
                $entry,
                array_merge($accumulatedErrors, $lastChunkResult['errors'] ?? []),
                array_merge($runState['warnings'] ?? [], $lastChunkResult['warnings'] ?? []),
                $projectId
            );
        }
        // clear run state
        $this->importRunState->clear($jobId, $projectId);
        // delete the temp file
        @unlink($job['csvPath']);
        // delete the system level job metadata
        $this->removeSystemSetting("import_job_$jobId");
        // remove the job from the queue
        $this->importJobQueue->remove($jobId);
        // remove the lock
        $this->removeSystemSetting('import_active_job');
    }

    /**
     * @param $action
     * @param $payload
     * @param $project_id
     * @param $record
     * @param $instrument
     * @param $event_id
     * @param $repeat_instance
     * @param $survey_hash
     * @param $response_id
     * @param $survey_queue_hash
     * @param $page
     * @param $page_full
     * @param $user_id
     * @param $group_id
     * @return array
     */
    function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id): array
    {
        return match ($action) {
            'get_project_structure' => $this->projectController->get(),
            'get_project_structure_hash' => $this->projectController->getProjectStructureHash(),
            'save_mapping' => $this->mappingController->save($payload),
            'update_mapping' => $this->mappingController->update($payload),
            'update_mapping_status' => $this->mappingController->updateStatus($payload),
            'copy_mapping' => $this->mappingController->copy($payload),
            'delete_mapping' => $this->mappingController->delete($payload),
            'get_mapping' => $this->mappingController->get($payload),
            'get_mappings' => $this->mappingController->getAll(),
            'get_logs' => $this->importLogController->getAll(),
            'get_active_jobs' => $this->importLogController->getActiveJobs(),
            'cancel_import_job' => $this->importLogController->cancel($payload),
            'validate_csv' => $this->csvController->validateAndPreview($payload),
            'preview_regex' => $this->regexController->preview($payload),
            default => ['success' => false, 'errors' => ['Invalid action']],
        };
    }
}
