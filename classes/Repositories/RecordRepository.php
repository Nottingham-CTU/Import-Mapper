<?php

namespace Nottingham\ImportMapper\Repositories;

use Exception;
use ExternalModules\ExternalModules;
use Nottingham\ImportMapper\ImportMapper;
use Nottingham\ImportMapper\Models\ProjectStructure;
use Nottingham\ImportMapper\Services\ProjectService;
use Records;
use REDCap;

/**
 * Data access class for REDCap records
 */
final class RecordRepository
{
    private ?bool $crnEnabled = null;

    /**
     * Constructor
     *
     * @param ImportMapper $module The external module instance
     * @param ProjectService $projectService Provides cached Project objects
     */
    public function __construct(
        private readonly ImportMapper $module,
        private readonly ProjectService $projectService,
    ) {
    }

    /**
     * Bulk-fetch all value-recordId pairs for a matching field using a SQL query.
     *
     * SQL is used because getData was too slow
     *
     * @param string $fieldName REDCap field name to index
     * @param string|null $eventName Event name for longitudinal projects
     * @param int|null $projectId Project ID
     * @param array $filterValues Unique CSV values to filter by; when empty all values are returned
     * @return array<string, string> Map of field value to record ID
     * @throws Exception
     */
    public function fetchAllMatchingRecordIds(string $fieldName, ?string $eventName = null, ?int $projectId = null, array $filterValues = []): array
    {
        $resolvedProjectId = $projectId ?? $this->module->getProjectId();
        $structure = $this->projectService->get($projectId);

        $table = Records::getDataTable($resolvedProjectId);

        $query = $this->module->createQuery();
        $query->add(
            "SELECT record, value FROM $table WHERE project_id = ? AND field_name = ?",
            [$resolvedProjectId, $fieldName]
        );

        if ($eventName) {
            $eventId = $structure->getEventIdByName($eventName);
            if ($eventId === null) {
                throw new Exception("Cannot resolve event ID for event name '$eventName'.");
            }
            $query->add('AND event_id = ?', [$eventId]);
        }

        if (!empty($filterValues)) {
            $query->add('AND')->addInClause('value', $filterValues);
        }

        // Matches non-repeating rows and instance 1 of repeating rows (REDCap stores instance 1 as NULL).
        $query->add("AND value != '' AND instance IS NULL");

        $result = $query->execute();
        $map = [];
        while ($row = $result->fetch_assoc()) {
            $record = (string)$row['record'];
            $value = (string)$row['value'];
            if ($record !== '' && $value !== '') {
                $map[$value] = $record;
            }
        }
        return $map;
    }

    /**
     * Bulk-fetch instance data for multiple records using a SQL query.
     * Returns: [recordId => [value => instanceNum]]
     *
     * Uses COALESCE(instance, 1) to handle REDCap's storage convention where
     * instance 1 of a repeating form/event is stored as NULL in the data table.
     *
     * @param array $recordIds Record IDs to fetch
     * @param string|null $eventName Event name
     * @param string $fieldName REDCap field name to index
     * @param int|null $projectId Project ID
     * @return array<string, array<string, int>>
     * @throws Exception
     */
    public function fetchAllInstancesByFieldBulk(
        array   $recordIds,
        ?string $eventName,
        string  $fieldName,
        ?int    $projectId = null
    ): array
    {
        if (empty($recordIds)) {
            return [];
        }

        $resolvedProjectId = $projectId ?? $this->module->getProjectId();
        $structure = $this->projectService->get($projectId);

        $table = Records::getDataTable($resolvedProjectId);

        $query = $this->module->createQuery();
        $query->add(
            "SELECT record, COALESCE(instance, 1) AS instance, value FROM $table WHERE project_id = ? AND field_name = ?",
            [$resolvedProjectId, $fieldName]
        );

        if ($eventName) {
            $eventId = $structure->getEventIdByName($eventName);
            if ($eventId === null) {
                throw new Exception("Cannot resolve event ID for event name '$eventName'.");
            }
            $query->add('AND event_id = ?', [$eventId]);
        }

        $query->add('AND')->addInClause('record', $recordIds);
        $query->add("AND value != ''");

        $result = $query->execute();

        $map = [];
        while ($row = $result->fetch_assoc()) {
            $record   = (string)$row['record'];
            $value    = (string)$row['value'];
            $instance = (int)$row['instance'];
            if ($record !== '' && $value !== '') {
                $map[$record][$value] = $instance;
            }
        }
        return $map;
    }

    /**
     * Save records to REDCap
     *
     * @param array $records Array of records to save
     * @param int|null $projectId ProjectStructure ID
     * @return array Response from REDCap::saveData
     */
    public function save(array $records, ?int $projectId = null): array
    {
        if ($projectId !== null) {
            return REDCap::saveData($projectId, 'json-array', $records);
        }
        return REDCap::saveData('json-array', $records);
    }

    /**
     * Reserve a new record ID
     *
     * If the Custom Record Naming (CRN) external module is enabled for the project,
     * its createRecord() method is used instead of REDCap::reserveNewRecordId().
     *
     * @param int|null $projectId ProjectStructure ID
     * @param ProjectStructure|null $project ProjectStructure model
     * @param string|null $dag DAG unique name for the current row
     * @return string new record ID
     * @throws Exception
     */
    public function reserveNewRecordId(
        ?int                 $projectId = null,
        ?ProjectStructure $project = null,
        ?string              $dag = null
    ): string
    {
        $resolvedProjectId = $projectId ?? $this->module->getProjectId();

        $this->crnEnabled ??= $this->module->isModuleEnabled('custom_record_naming', $resolvedProjectId);

        if ( $this->crnEnabled )
        {
            $crnModule = ExternalModules::getModuleInstance('custom_record_naming');
            if ( $crnModule !== null && method_exists($crnModule, 'createRecord') )
            {
                $eventId = $project?->getFirstEventId() ?? null;
                $dagId = ( $dag !== null && $project !== null )
                    ? $project->getDagIdByUniqueName($dag)
                    : null;

                $recordName = $crnModule->createRecord($eventId, $dagId);
                if ($recordName !== null)
                {
                    return (string)$recordName;
                }
            }
        }

        return REDCap::reserveNewRecordId($resolvedProjectId);
    }

    /**
     * Get the record ID field name
     *
     * @param int|null $projectId ProjectStructure ID (required in cron context)
     * @return string Record ID field name
     */
    public function getRecordIdField(?int $projectId = null): string
    {
        return $this->projectService->get($projectId)->recordIdFieldName;
    }

}
