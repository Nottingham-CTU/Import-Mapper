<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents an import error
 */
final readonly class ImportError
{
    /**
     * @param ImportErrorOrigin $origin
     * @param string $message
     * @param int|null $rowNumber
     * @param string|null $csvFieldName
     * @param string|null $csvValue
     */
    private function __construct(
        public ImportErrorOrigin $origin,
        public string            $message,
        public ?int              $rowNumber,
        public ?string           $csvFieldName,
        public ?string           $csvValue
    )
    {
    }

    /**
     * Create error from mapping validation
     */
    public static function mapping(string $message): self
    {
        return new self(
            ImportErrorOrigin::MAPPING,
            $message,
            null,
            null,
            null
        );
    }

    /**
     * Create error from CSV structure validation
     */
    public static function csvStructure(string $message): self
    {
        return new self(
            ImportErrorOrigin::CSV_STRUCTURE,
            $message,
            null,
            null,
            null
        );
    }

    /**
     * Create error from CSV data validation
     */
    public static function csvData(string $message, ?int $rowNumber = null, ?string $csvFieldName = null, ?string $csvValue = null): self
    {
        return new self(
            ImportErrorOrigin::CSV_DATA,
            $message,
            $rowNumber,
            $csvFieldName,
            $csvValue
        );
    }

    /**
     * Create error from data transformation
     */
    public static function transformation(string $message, int $rowNumber, ?string $csvFieldName = null, ?string $csvValue = null): self
    {
        return new self(
            ImportErrorOrigin::TRANSFORMATION,
            $message,
            $rowNumber,
            $csvFieldName,
            $csvValue
        );
    }

    /**
     * Serialize this error to a plain array (for JSON run state storage)
     */
    public function toArray(): array
    {
        return [
            'origin' => $this->origin->value,
            'message' => $this->message,
            'rowNumber' => $this->rowNumber,
            'csvFieldName' => $this->csvFieldName,
            'csvValue' => $this->csvValue,
        ];
    }

    /**
     * Create an ImportError from a serialized array (e.g., from the log)
     *
     * @param array $data Associative array with origin, message, rowNumber, csvFieldName, csvValue
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ImportErrorOrigin::from($data['origin']),
            $data['message'],
            $data['rowNumber'] ?? null,
            $data['csvFieldName'] ?? null,
            $data['csvValue'] ?? null,
        );
    }

    /**
     * Create error from REDCap saveData
     */
    public static function redcapSave(string $message, ?int $rowNumber = null): self
    {
        return new self(
            ImportErrorOrigin::REDCAP_SAVE,
            $message,
            $rowNumber,
            null,
            null
        );
    }
}
