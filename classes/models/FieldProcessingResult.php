<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents the result of processing a single field within a row
 */
final readonly class FieldProcessingResult
{
    /**
     * @param bool $hasError
     * @param bool $isSkipped
     * @param ImportError|null $error
     * @param string|null $compositeKey
     * @param array|null $record
     * @param string|null $fieldName
     * @param string|null $value
     */
    private function __construct(
        public bool         $hasError,
        public bool         $isSkipped,
        public ?ImportError $error = null,
        public ?string      $compositeKey = null,
        public ?array       $record = null,
        public ?string      $fieldName = null,
        public ?string      $value = null
    )
    {
    }

    /**
     * Create a successful processing result
     *
     * @param string $compositeKey Unique key for this event/instance/instrument combination
     * @param array $record Initialized record structure
     * @param string $fieldName REDCap field name
     * @param string|null $value Field value
     * @return self
     */
    public static function success(
        string  $compositeKey,
        array   $record,
        string  $fieldName,
        ?string $value
    ): self
    {
        return new self(
            hasError: false,
            isSkipped: false,
            compositeKey: $compositeKey,
            record: $record,
            fieldName: $fieldName,
            value: $value
        );
    }

    /**
     * Create an error result
     *
     * @param string $message Error message
     * @param int|null $rowNumber Row number
     * @param string|null $csvFieldName CSV field name
     * @param string|null $csvValue CSV value
     * @return self
     */
    public static function error(string $message, ?int $rowNumber = null, ?string $csvFieldName = null, ?string $csvValue = null): self
    {
        return new self(
            hasError: true,
            isSkipped: false,
            error: ImportError::transformation($message, $rowNumber ?? 0, $csvFieldName, $csvValue)
        );
    }

    /**
     * Create a skipped result (field not present in data)
     *
     * @return self
     */
    public static function skipped(): self
    {
        return new self(
            hasError: false,
            isSkipped: true
        );
    }
}
