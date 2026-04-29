<?php

namespace Nottingham\ImportMapper\Services;

use Nottingham\ImportMapper\Models\ProjectStructure;

/**
 * Transformer for building REDCap record structures
 * Constructs properly formatted REDCap records with all required fields
 */
final readonly class RecordBuilder
{
    /**
     * Initialize a new REDCap record with base fields
     *
     * @param string $recordId The record ID
     * @param string $recordIdFieldName The record ID field name
     * @param string|null $eventName The event name
     * @param int|string|null $repeatInstance Repeat instance number or "new"
     * @param string $repeatInstrument Repeat instrument name
     * @param ProjectStructure $projectStructure The project structure
     * @param string|null $dag DAG unique name or null
     * @return array Initialized record array
     */
    public function initialize(
        string           $recordId,
        string           $recordIdFieldName,
        ?string          $eventName,
        int|string|null  $repeatInstance,
        string           $repeatInstrument,
        ProjectStructure $projectStructure,
        ?string          $dag
    ): array
    {
        $record = [$recordIdFieldName => $recordId];

        // Add event field name if longitudinal
        if ($projectStructure->isLongitudinal && $eventName !== null) {
            $record['redcap_event_name'] = $eventName;
        }

        // Add DAG field if determined
        if ($dag !== null) {
            $record['redcap_data_access_group'] = $dag;
        }

        // Add repeat instance fields if applicable
        if ($repeatInstance !== null) {
            $this->addRepeatFields($record, $eventName, $repeatInstance, $repeatInstrument, $projectStructure);
        }

        return $record;
    }

    /**
     * Add a field to a record
     *
     * @param array $record The record to add to (passed by reference)
     * @param string $fieldName The field name
     * @param mixed $value The field value
     * @return void
     */
    public function addField(array &$record, string $fieldName, mixed $value): void
    {
        $record[$fieldName] = $value;
    }

    /**
     * Add repeat instance fields to a record
     *
     * @param array $record The record (passed by reference)
     * @param string|null $eventName The event name
     * @param int|string|null $repeatInstance Repeat instance
     * @param string $repeatInstrument Repeat instrument name
     * @param ProjectStructure $project The project structure
     * @return void
     */
    private function addRepeatFields(
        array            &$record,
        ?string          $eventName,
        int|string|null  $repeatInstance,
        string           $repeatInstrument,
        ProjectStructure $project
    ): void
    {
        $isRepeatingEvent = $project->isLongitudinal
            && $eventName
            && $project->isRepeatingEvent($eventName);

        if (!$isRepeatingEvent) {
            // Repeating form → set both
            $record['redcap_repeat_instrument'] = $repeatInstrument;
        }
        $record['redcap_repeat_instance'] = $repeatInstance;
    }
}
