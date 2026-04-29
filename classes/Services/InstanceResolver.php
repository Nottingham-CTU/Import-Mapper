<?php

namespace Nottingham\ImportMapper\Services;

use Exception;
use Nottingham\ImportMapper\Repositories\RecordRepository;

final class InstanceResolver
{
    /**
     * Cache that maps value to instanceNum for each loaded combination.
     * Key: "$recordId::$eventName::$fieldName::$value"
     *
     * @var array<string, int|string>
     */
    private array $instanceCache = [];

    public function __construct(private readonly RecordRepository $recordRepository)
    {
    }

    /**
     * Bulk-prefetch instance data for multiple records at once.
     * Populates the cache so subsequent resolve() calls are cache hits.
     *
     * @param array $recordIds Record IDs to prefetch
     * @param string|null $eventName Event name
     * @param string $fieldName Field name to match on
     * @param int|null $projectId Project ID
     * @throws Exception
     */
    public function prefetch(
        array   $recordIds,
        ?string $eventName,
        string  $fieldName,
        ?int    $projectId = null
    ): void
    {
        $bulkData = $this->recordRepository->fetchAllInstancesByFieldBulk(
            $recordIds, $eventName, $fieldName, $projectId
        );

        foreach ($bulkData as $recordId => $instanceMap) {
            $contextKey = $recordId . '::' . ($eventName ?? '') . '::' . $fieldName;
            foreach ($instanceMap as $value => $instanceNum) {
                $this->instanceCache[$contextKey . '::' . $value] = $instanceNum;
            }
        }
    }

    public function resolve(
        string  $recordId,
        ?string $eventName,
        string  $fieldName,
        string  $value
    ): int|string
    {
        $contextKey = $recordId . '::' . ($eventName ?? '') . '::' . $fieldName;
        return $this->instanceCache[$contextKey . '::' . $value] ?? 'new';
    }
}
