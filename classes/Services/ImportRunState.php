<?php

namespace Nottingham\ImportMapper\Services;

use ExternalModules\AbstractExternalModule;

/**
 * Persists per-job run state between cron chunks using project settings.
 */
final readonly class ImportRunState
{
    public function __construct(private AbstractExternalModule $module)
    {
    }

    /**
     * Load persisted chunk run state for an import job.
     * Returns null if no state exists.
     */
    public function get(string $jobId, int $projectId): ?array
    {
        $json = $this->module->getSystemSetting("import_run_{$projectId}_$jobId");
        if ($json === null) {
            return null;
        }
        $state = json_decode($json, true);
        return is_array($state) ? $state : null;
    }

    /**
     * Persist chunk run state for an import job between cron runs.
     */
    public function save(string $jobId, int $projectId, array $state): void
    {
        $this->module->setSystemSetting("import_run_{$projectId}_$jobId", json_encode($state));
    }

    /**
     * Remove persisted chunk run state after a job completes or is canceled.
     */
    public function clear(string $jobId, int $projectId): void
    {
        $this->module->removeSystemSetting("import_run_{$projectId}_$jobId");
    }

    /**
     * Build a fresh initial run state array for a newly queued job.
     */
    public function buildInitialState(
        string $jobId,
        string $mappingId,
        string $mappingName,
        string $username,
        string $queuedAt
    ): array {
        return [
            'jobId'                   => $jobId,
            'mappingId'               => $mappingId,
            'mappingName'             => $mappingName,
            'username'                => $username,
            'status'                  => 'queued',
            'queuedAt'                => $queuedAt,
            'startedAt'               => null,
            'csvOffset'               => 0,
            'rowsProcessed'           => 0,
            'itemsUpdated'            => 0,
            'recordIds'               => [],
            'totalRows'               => 0,
            'mappingErrorCount'       => 0,
            'csvStructureErrorCount'  => 0,
            'csvDataErrorCount'       => 0,
            'transformationErrorCount'=> 0,
            'redcapSaveErrorCount'    => 0,
            'warningCount'            => 0,
        ];
    }
}
