<?php

namespace Nottingham\ImportMapper\Services;

use ExternalModules\AbstractExternalModule;

/**
 * Manages the system-level job index and per-job system settings.
 */
final readonly class ImportJobQueue
{
    public function __construct(private AbstractExternalModule $module)
    {
    }

    /**
     * Return the current job index as an array of jobId strings.
     */
    public function getIndex(): array
    {
        $index = json_decode($this->module->getSystemSetting('import_job_index') ?? '[]', true);
        return is_array($index) ? $index : [];
    }

    /**
     * Return the stored job record for a given jobId, or null if not found.
     */
    public function getJob(string $jobId): ?array
    {
        $json = $this->module->getSystemSetting("import_job_$jobId");
        if ($json === null) {
            return null;
        }
        $job = json_decode($json, true);
        return is_array($job) ? $job : null;
    }

    /**
     * Add a jobId to the index
     */
    public function add(string $jobId): void
    {
        $index = $this->getIndex();
        if (!in_array($jobId, $index, true)) {
            $index[] = $jobId;
            $this->module->setSystemSetting('import_job_index', json_encode($index));
        }
    }

    /**
     * Remove a jobId from the index.
     */
    public function remove(string $jobId): void
    {
        $index = $this->getIndex();
        $index = array_values(array_filter($index, fn($id) => $id !== $jobId));
        $this->module->setSystemSetting('import_job_index', json_encode($index));
    }

    /**
     * Remove jobs that are older than 72 hours or have no matching system setting.
     */
    public function cleanupOrphaned(): void
    {
        $cutoff = time() - 259200;
        foreach ($this->getIndex() as $jobId) {
            $job = $this->getJob($jobId);
            if ($job === null) {
                $this->remove($jobId);
                continue;
            }
            if (isset($job['queuedAt']) && $job['queuedAt'] < $cutoff) {
                @unlink($job['csvPath'] ?? '');
                $this->module->removeSystemSetting("import_job_$jobId");
                $this->remove($jobId);
            }
        }
    }
}
