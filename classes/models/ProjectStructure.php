<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents a REDCap project's structure
 */
final readonly class ProjectStructure
{
    /**
     * @param bool $isLongitudinal
     * @param array $repeatingEventNames
     * @param array $repeatingFormsByEvent
     * @param array $repeatingForms
     * @param array $allEventNames
     * @param array $allFormNamesByEvent
     * @param array $allFormNames
     * @param array $fieldsByForm
     * @param array $fieldTypesByForm
     * @param array $dataAccessGroups
     * @param string $recordIdFieldName
     */
    private function __construct(
        public bool   $isLongitudinal,
        private array $repeatingEventNames,
        private array $repeatingFormsByEvent,
        private array $repeatingForms,
        private array $allEventNames,
        private array $allFormNamesByEvent,
        private array $allFormNames,
        private array $fieldsByForm,
        private array $fieldTypesByForm,
        public array  $dataAccessGroups,
        public string $recordIdFieldName
    )
    {
    }

    /**
     * Return the integer event ID of the first event in the project, or null if none.
     */
    public function getFirstEventId(): ?int
    {
        $firstName = array_key_first($this->allEventNames);
        return $firstName !== null ? (int)$this->allEventNames[$firstName] : null;
    }

    /**
     * Return the group ID for a given DAG unique name, or null if not found.
     */
    public function getDagIdByUniqueName(string $uniqueName): ?int
    {
        $id = $this->dataAccessGroups[$uniqueName] ?? null;
        return $id !== null ? (int)$id : null;
    }

    /**
     * Convert to array. Used for caching between cron chunks.
     */
    public function toSerializable(): array
    {
        return [
            'is_longitudinal' => $this->isLongitudinal,
            'repeating_event_names' => $this->repeatingEventNames,
            'repeating_forms_by_event' => $this->repeatingFormsByEvent,
            'repeating_forms' => $this->repeatingForms,
            'all_event_names' => $this->allEventNames,
            'all_form_names_by_event' => $this->allFormNamesByEvent,
            'all_form_names' => $this->allFormNames,
            'fields_by_form' => $this->fieldsByForm,
            'field_types_by_form' => $this->fieldTypesByForm,
            'data_access_groups' => $this->dataAccessGroups,
            'record_id_field_name' => $this->recordIdFieldName,
        ];
    }

    /**
     * Reconstruct from a toSerializable() array
     */
    public static function fromSerializable(array $data): self
    {
        return new self(
            $data['is_longitudinal'] ?? false,
            $data['repeating_event_names'] ?? [],
            $data['repeating_forms_by_event'] ?? [],
            $data['repeating_forms'] ?? [],
            $data['all_event_names'] ?? [],
            $data['all_form_names_by_event'] ?? [],
            $data['all_form_names'] ?? [],
            $data['fields_by_form'] ?? [],
            $data['field_types_by_form'] ?? [],
            $data['data_access_groups'] ?? [],
            $data['record_id_field_name'] ?? ''
        );
    }

    /**
     * Convert to array for API output and project structure hashing.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'is_longitudinal' => $this->isLongitudinal,
            'repeating_event_names' => array_keys($this->repeatingEventNames),
            'repeating_forms_by_event' => array_map('array_keys', $this->repeatingFormsByEvent),
            'repeating_forms' => array_keys($this->repeatingForms),
            'all_event_names' => $this->allEventNames,
            'all_form_names_by_event' => $this->allFormNamesByEvent,
            'all_form_names' => $this->allFormNames,
            'fields_by_form' => $this->fieldsByForm,
            'field_types_by_form' => $this->fieldTypesByForm,
            'data_access_groups' => $this->dataAccessGroups,
            'record_id_field_name' => $this->recordIdFieldName,
        ];
    }

    /**
     * Return the integer event ID for a given event unique name, or null if not found.
     */
    public function getEventIdByName(string $eventName): ?int
    {
        $id = $this->allEventNames[$eventName] ?? null;
        return $id !== null ? (int)$id : null;
    }

    /**
     * Check if an event is repeating
     *
     * @param string $eventName Event name to check
     * @return bool
     */
    public function isRepeatingEvent(string $eventName): bool
    {
        return isset($this->repeatingEventNames[$eventName]);
    }

    /**
     * Check if a form is repeating for a given event
     *
     * @param string $formName Form name
     * @param string|null $eventName Event name
     * @return bool
     */
    public function isRepeatingForm(string $formName, ?string $eventName): bool
    {
        if ($this->isLongitudinal && isset($eventName)) {
            return isset($this->repeatingFormsByEvent[$eventName][$formName]);
        } else {
            return isset($this->repeatingForms[$formName]);
        }
    }

    /**
     * Check if an event exists in the project
     *
     * @param string $eventName Event name to check
     * @return bool True if event exists, false otherwise
     */
    public function exists(string $eventName): bool
    {
        return array_key_exists($eventName, $this->allEventNames);
    }

    /**
     * Get repeat instrument name for repeating forms using FieldMapping
     *
     * @param FieldMapping $fieldMapping Field mapping
     * @param int|string|null $repeatInstance Repeat instance
     * @return string Instrument name or empty string
     */
    public function getRepeatInstrument(
        FieldMapping $fieldMapping, int|string|null $repeatInstance, ?string $eventName
    ): string
    {
        $isRepeatingEvent = $this->isLongitudinal
            && $eventName
            && $this->isRepeatingEvent($eventName);

        if ($isRepeatingEvent) {
            return '';
        }

        return $fieldMapping->redcapFormName;
    }
}
