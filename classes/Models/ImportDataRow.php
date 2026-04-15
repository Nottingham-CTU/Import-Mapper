<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents a single row of data
 */
final readonly class ImportDataRow
{
    public array $data;


    /**
     * @param int $rowNumber Row number (1-based, typically starts at 2 after header row)
     * @param array $headers Column headers
     * @param array $values Row values (indexed array)
     * @param bool $valid
     * @param string $invalidReason
     */
    public function __construct(public int $rowNumber, array $headers, array $values, public bool $valid, public string $invalidReason = "")
    {
        $paddedValues = array_slice(
            array_pad(array_values($values), count($headers), ''),
            0,
            count($headers)
        );
        $this->data = array_combine($headers, $paddedValues);
    }

    /**
     * Get value for a specific column
     *
     * @param string $fieldName Column name
     * @return string|null Field value or null if not found
     */
    public function get(string $fieldName): ?string
    {
        return $this->data[$fieldName] ?? null;
    }

    /**
     * Check if field exists
     */
    public function has(string $fieldName): bool
    {
        return array_key_exists($fieldName, $this->data);
    }

    /**
     * Check if row is empty (all values are empty strings)
     */
    public function isEmpty(): bool
    {
        return array_all($this->data, fn($value) => trim($value) === '');
    }
}
