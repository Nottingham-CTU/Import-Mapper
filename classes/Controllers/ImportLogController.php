<?php

namespace Nottingham\ImportMapper\Controllers;

use Nottingham\ImportMapper\Repositories\ImportLogRepository;
use Nottingham\ImportMapper\Services\ImportJobManager;
use Nottingham\ImportMapper\Services\PermissionService;

/**
 * Import log AJAX actions
 */
final readonly class ImportLogController
{
    /**
     * Constructor
     *
     * @param ImportLogRepository $importLogRepository Import log repo
     * @param PermissionService $permissionService Permission checking service
     * @param ImportJobManager $jobManager Job manager for active job queries and cancellation
     */
    public function __construct(
        private ImportLogRepository $importLogRepository,
        private PermissionService   $permissionService,
        private ImportJobManager    $jobManager
    )
    {
    }

    /**
     * Get all logs
     *
     * @return array Response with success status and array of import logs
     */
    public function getAll(): array
    {
        if (!$this->permissionService->canViewLogs()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to view logs']
            ];
        }

        return [
            'success' => true,
            'logs' => $this->importLogRepository->findAll()
        ];
    }

    /**
     * Get all active (queued/in_progress) jobs for this project, from run state.
     *
     * @return array
     */
    public function getActiveJobs(): array
    {
        if (!$this->permissionService->canViewLogs()) {
            return ['success' => false, 'errors' => ['You do not have permission to view imports']];
        }
        return $this->jobManager->getActiveJobs($this->jobManager->getProjectId());
    }

    /**
     * Cancel a queued or in-progress import job.
     *
     * @param array $payload Must contain 'jobId'
     * @return array
     */
    public function cancel(array $payload): array
    {
        if (!$this->permissionService->canImport()) {
            return ['success' => false, 'errors' => ['You do not have permission to cancel imports']];
        }
        $jobId = $payload['jobId'] ?? null;
        if (!$jobId) {
            return ['success' => false, 'errors' => ['Missing jobId']];
        }
        return $this->jobManager->cancelImportJob($jobId, $this->jobManager->getProjectId());
    }

}
