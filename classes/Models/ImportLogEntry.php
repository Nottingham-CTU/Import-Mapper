<?php

namespace Nottingham\ImportMapper\Models;

final readonly class ImportLogEntry
{
    /**
     * @param string $mappingId
     * @param string $mappingName
     * @param string $status
     * @param string|null $outcome
     * @param int $rowsProcessed
     * @param int $itemsUpdated
     * @param int $recordsUpdated
     * @param int $totalRows
     * @param int $mappingErrorCount
     * @param int $csvStructureErrorCount
     * @param int $csvDataErrorCount
     * @param int $transformationErrorCount
     * @param int $redcapSaveErrorCount
     * @param int $warningCount
     * @param string $id
     * @param string $username
     * @param string|null $timestamp
     * @param string $jobId
     * @param string|null $queuedAt
     * @param string|null $startedAt
     */
    public function __construct(
        public string $mappingId,
        public string $mappingName,
        public string $status,
        public ?string $outcome = null,
        public int $rowsProcessed = 0,
        public int $itemsUpdated = 0,
        public int $recordsUpdated = 0,
        public int $totalRows = 0,
        public int $mappingErrorCount = 0,
        public int $csvStructureErrorCount = 0,
        public int $csvDataErrorCount = 0,
        public int $transformationErrorCount = 0,
        public int $redcapSaveErrorCount = 0,
        public int $warningCount = 0,
        public string $id = '',
        public string $username = '',
        public ?string $timestamp = null,
        public string $jobId = '',
        public ?string $queuedAt = null,
        public ?string $startedAt = null,
    ) {}
}