<?php

namespace Nottingham\ImportMapper\Models;

final readonly class MatchingConfig
{
    /**
     * @param bool $recordEnabled
     * @param string|null $recordFieldMappingId
     * @param bool $eventEnabled
     * @param array $eventFieldMappingIds
     * @param bool $formEnabled
     * @param array $formFieldMappingIds
     * @param DagConfig $dagConfig
     */
    private function __construct(
        public bool      $recordEnabled,
        public ?string   $recordFieldMappingId,
        public bool      $eventEnabled,
        public array     $eventFieldMappingIds,
        public bool      $formEnabled,
        public array     $formFieldMappingIds,
        public DagConfig $dagConfig
    )
    {
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $record = $data['record'] ?? [];
        $event = $data['event'] ?? [];
        $form = $data['form'] ?? [];
        $dag = $data['dag'] ?? [];

        if (!($dag['enabled'] ?? false)) {
            $dagMatchConfig = DagConfig::disabled();
        } else {
            $dagMode = DagMode::from($dag['mode']);
            if ($dagMode === DagMode::CSV_FIELD) {
                $dagMatchConfig = DagConfig::fromCsvFieldName($dag['csvFieldName']);
            } else if ($dagMode === DagMode::SAME_FOR_ALL){
                $dagMatchConfig = DagConfig::fromUniqueName($dag['dagUniqueName']);
            }
            else{
                $dagMatchConfig = DagConfig::fromSelectMode();   
            }
        }

        return new self(
            (bool)($record['enabled'] ?? false),
            $record['fieldMappingId'] ?? null,
            (bool)($event['enabled'] ?? false),
            is_array($event['fieldMappingIds'] ?? null) ? $event['fieldMappingIds'] : [],
            (bool)($form['enabled'] ?? false),
            is_array($form['fieldMappingIds'] ?? null) ? $form['fieldMappingIds'] : [],
            $dagMatchConfig
        );
    }

    /**
     * @return array[]
     */
    public function toArray(): array
    {
        return [
            'record' => [
                'enabled' => $this->recordEnabled,
                'fieldMappingId' => $this->recordFieldMappingId ?? '',
            ],
            'event' => [
                'enabled' => $this->eventEnabled,
                'fieldMappingIds' => array_values($this->eventFieldMappingIds),
            ],
            'form' => [
                'enabled' => $this->formEnabled,
                'fieldMappingIds' => array_values($this->formFieldMappingIds),
            ],
            'dag' => [
                'enabled' => $this->dagConfig->enabled,
                'mode' => $this->dagConfig->mode?->value ?? '',
                'dagUniqueName' => $this->dagConfig->dagUniqueName ?? '',
                'csvFieldName' => $this->dagConfig->dagCsvFieldName ?? '',
            ],
        ];
    }
}
