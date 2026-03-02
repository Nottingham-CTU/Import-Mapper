<?php

namespace Nottingham\ImportMapper\Services;

use Exception;
use ExternalModules\AbstractExternalModule;
use Nottingham\ImportMapper\Models\ImportLogEntry;
use Nottingham\ImportMapper\Repositories\ImportLogRepository;
use Nottingham\ImportMapper\Repositories\MappingRepository;
use Random\RandomException;

/**
 * Manages the import job queue and the current run state of each job
 */
final readonly class ImportJobManager
{
    public function __construct(
        private AbstractExternalModule $module,
        private ImportJobQueue         $importJobQueue,
        private ImportRunState         $importRunState,
        private ImportLogRepository    $importLogRepository,
        private MappingRepository      $mappingRepository,
    ) {
    }

    /**
     * Return the current project ID from the framework module.
     */
    public function getProjectId(): int
    {
        return (int)$this->module->getProjectId();
    }

    /**
     * Return the mapping IDs that have an active (queued, in_progress, or cancelling) job
     * in the given project. Used to prevent editing a mapping while an import is running.
     */
    public function getLockedMappingIds(int $projectId): array
    {
        $lockedIds = [];
        foreach ($this->importJobQueue->getIndex() as $jobId) {
            $job = $this->importJobQueue->getJob($jobId);
            if ($job === null) {
                continue;
            }
            if ((int)($job['projectId'] ?? 0) !== $projectId) {
                continue;
            }
            $runState = $this->importRunState->get($jobId, $projectId);
            if ($runState === null) {
                continue;
            }
            $lockedIds[] = $job['mappingId'];
        }
        return array_values(array_unique($lockedIds));
    }

    /**
     * Return current run state for all active jobs in this project.
     * Used by the frontend to display progress without reading the log.
     */
    public function getActiveJobs(int $projectId): array
    {
        $jobs = [];
        foreach ($this->importJobQueue->getIndex() as $jobId) {
            $job = $this->importJobQueue->getJob($jobId);
            if ($job === null) {
                continue;
            }
            if ((int)($job['projectId'] ?? 0) !== $projectId) {
                continue;
            }
            $runState = $this->importRunState->get($jobId, $projectId);
            if ($runState === null) {
                continue;
            }
            $jobs[] = [
                'jobId'            => $jobId,
                'mappingId'        => $runState['mappingId'] ?? $job['mappingId'],
                'mappingName'      => $runState['mappingName'] ?? $job['mappingName'],
                'status'           => in_array($runState['status'] ?? '', ['in_progress', 'cancelling'])
                    ? 'in_progress'
                    : ($runState['status'] ?? 'queued'),
                'rowsProcessed'    => $runState['rowsProcessed'] ?? 0,
                'totalRows'        => $runState['totalRows'] ?? 0,
                'itemsUpdated'     => $runState['itemsUpdated'] ?? 0,
                'chunkCompletedAt' => $runState['chunkCompletedAt'] ?? null,
                'username'         => $runState['username'] ?? '',
                'queuedAt'         => $runState['queuedAt'] ?? null,
                'startedAt'        => $runState['startedAt'] ?? null,
                'cancelling'       => ($runState['status'] ?? '') === 'cancelling',
            ];
        }
        return ['success' => true, 'jobs' => $jobs];
    }

    /**
     * Queue a CSV import job.
     * Called from upload.php page — saves the uploaded CSV to a temp file, creates initial run state,
     * and stores the job in the system-level job index so the cron can pick it up.
     *
     * @param string $mappingId The mapping ID to use
     * @param string $tmpFilePath The PHP temporary file path from $_FILES
     * @return array Response with success status and jobId
     * @throws RandomException
     * @throws Exception
     */
    public function queueImportJob(string $mappingId, string $tmpFilePath): array
    {
        $mapping = $this->mappingRepository->findById($mappingId);
        $mappingName = $mapping->name;

        $projectId = $this->getProjectId();
        $jobId = bin2hex(random_bytes(16));

        $dir = sys_get_temp_dir() . '/import_mapper';
        @mkdir($dir, 0700, true);
        $csvPath = "$dir/csv_{$projectId}_$jobId.csv";

        if (!move_uploaded_file($tmpFilePath, $csvPath)) {
            return ['success' => false, 'errors' => ['Failed to save uploaded file']];
        }

        $notificationEmails = array_values(array_filter(
            (array)$this->module->getProjectSetting('notification-emails')
        ));

        $this->module->setSystemSetting("import_job_$jobId", json_encode([
            'jobId'              => $jobId,
            'projectId'          => $projectId,
            'mappingId'          => $mappingId,
            'mappingName'        => $mappingName,
            'csvPath'            => $csvPath,
            'queuedAt'           => time(),
            'notificationEmails' => $notificationEmails,
        ]));

        $this->importRunState->save(
            $jobId,
            $projectId,
            $this->importRunState->buildInitialState(
                jobId:       $jobId,
                mappingId:   $mappingId,
                mappingName: $mappingName,
                username:    $this->module->getUser()->getUsername(),
                queuedAt:    date('c'),
            )
        );

        $this->importJobQueue->add($jobId);

        return ['success' => true, 'jobId' => $jobId];
    }

    /**
     * Cancel a queued or in-progress import job.
     *
     * For a queued job: removes the job immediately, writes a 'canceled' log entry.
     * For an in-progress job: sets a cancel flag that the cron's progress callback detects.
     */
    public function cancelImportJob(string $jobId, int $projectId): array
    {
        $job = $this->importJobQueue->getJob($jobId);
        if ($job === null) {
            return ['success' => false, 'errors' => ['Job not found or already completed']];
        }

        $runState = $this->importRunState->get($jobId, $projectId);
        if ($runState === null) {
            return ['success' => false, 'errors' => ['Job state not found']];
        }

        if ($runState['status'] === 'queued') {
            @unlink($job['csvPath']);
            $this->importLogRepository->save(
                new ImportLogEntry(
                    mappingId:      $job['mappingId'],
                    mappingName:    $job['mappingName'],
                    status:         'cancelled',
                    rowsProcessed:  0,
                    itemsUpdated:   0,
                    recordsUpdated: 0,
                    username:       $runState['username'] ?? '',
                    queuedAt:       $runState['queuedAt'] ?? null,
                    startedAt:      $runState['startedAt'] ?? null,
                ),
                $projectId
            );
            $this->importRunState->clear($jobId, $projectId);
            $this->module->removeSystemSetting("import_job_$jobId");
            $this->importJobQueue->remove($jobId);
            return ['success' => true];
        }

        if ($runState['status'] === 'in_progress' || $runState['status'] === 'cancelling') {
            $runState['status'] = 'cancelling';
            $this->importRunState->save($jobId, $projectId, $runState);
            $this->module->log('cancelImportJob: status set to cancelling', ['job_id' => $jobId]);
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Job has already completed']];
    }
}
