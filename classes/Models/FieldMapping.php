<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents a field mapping from CSV to REDCap
 */
final readonly class FieldMapping
{
    public string $id;
    public string $csvFieldName;
    public ?string $redcapEventName;
    public string $redcapFormName;
    public string $redcapFieldName;
    public ?string $dateFormat;
    public ?array $valueMappings;
    public ?array $combineFields;
    public ?array $fieldRegex;

    /**
     * @param string $csvFieldName
     * @param ?string $redcapEventName
     * @param string $redcapFormName
     * @param string $redcapFieldName
     * @param string|null $dateFormat
     * @param array|null $valueMappings
     * @param array|null $combineFields
     * @param array|null $fieldRegex
     */
    public function __construct(
        string  $csvFieldName,
        ?string $redcapEventName,
        string  $redcapFormName,
        string  $redcapFieldName,
        ?string $dateFormat = null,
        ?array  $valueMappings = null,
        ?array  $combineFields = null,
        ?array  $fieldRegex = null
    )
    {
        $this->id = self::generateId($csvFieldName, $redcapEventName, $redcapFieldName);
        $this->csvFieldName = $csvFieldName;
        $this->redcapEventName = $redcapEventName;
        $this->redcapFormName = $redcapFormName;
        $this->redcapFieldName = $redcapFieldName;
        $this->dateFormat = $dateFormat;
        $this->valueMappings = $valueMappings;
        $this->combineFields = $combineFields;
        $this->fieldRegex = $fieldRegex;
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['csvFieldName'] ?? '',
            $data['redcapEventName'] ?: null,
            $data['redcapFormName'] ?? '',
            $data['redcapFieldName'] ?? '',
            $data['dateFormat'] ?? null,
            $data['valueMappings'] ?? null,
            $data['combineFields'] ?? null,
            $data['fieldRegex'] ?? null
        );
    }

    /**
     * Generate the composite ID for this field mapping
     */
    public static function generateId(
        string  $csvField,
        ?string $event,
        string  $field
    ): string
    {
        if ($event === null) {
            // Classic project
            return "$csvField::$field";
        }
        // Longitudinal project
        return "$csvField::$event::$field";
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'csvFieldName' => $this->csvFieldName,
            'redcapEventName' => $this->redcapEventName,
            'redcapFormName' => $this->redcapFormName,
            'redcapFieldName' => $this->redcapFieldName,
            'dateFormat' => $this->dateFormat,
            'valueMappings' => $this->valueMappings,
            'combineFields' => $this->combineFields,
            'fieldRegex' => $this->fieldRegex,
        ];
    }
}
