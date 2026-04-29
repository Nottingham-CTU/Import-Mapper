<?php

namespace Nottingham\ImportMapper\Services\Validation;

use Nottingham\ImportMapper\Models\ImportData;
use Nottingham\ImportMapper\Models\ImportError;
use Nottingham\ImportMapper\Models\Mapping;
use Nottingham\ImportMapper\Models\ValidationResult;

/**
 * Validator for CSV data
 * Validates data structure and headers against mapping configuration
 */
final readonly class CsvValidator
{
    /**
     * Validate csv data against mapping
     *
     * @param ImportData $data csv data to validate
     * @param Mapping $mapping Mapping configuration
     * @return ValidationResult Validation result
     */
    public function validate(ImportData $data, Mapping $mapping): ValidationResult
    {
        $errors = [];

        // Check for empty headers
        $headers = $data->headers;
        if (empty($headers)) {
            $errors[] = ImportError::csvStructure('CSV has no headers');
            return ValidationResult::failure($errors);
        }

        // Check for no data rows
        if ($data->totalRows === 0) {
            $errors[] = ImportError::csvStructure('CSV has no rows');
            return ValidationResult::failure($errors);
        }

        // Check for duplicate headers (excluding empty ones)
        $nonEmptyHeaders = array_filter($headers, function ($header) {
            return trim($header) !== '';
        });

        $uniqueNonEmpty = array_unique($nonEmptyHeaders);
        if (count($nonEmptyHeaders) !== count($uniqueNonEmpty)) {
            // Find which non-empty headers are duplicated
            $duplicates = array_diff_assoc($nonEmptyHeaders, $uniqueNonEmpty);
            $duplicateList = implode(', ', $duplicates);
            $errors[] = ImportError::csvStructure("Duplicate column headers, affects headers: $duplicateList");
        }

        // Check for empty headers
        $emptyHeaderColumns = [];
        foreach ($headers as $index => $header) {
            if (trim($header) === '') {
                $emptyHeaderColumns[] = $index + 1; // 1-based column numbers
            }
        }

        if (!empty($emptyHeaderColumns)) {
            $columnList = implode(', ', $emptyHeaderColumns);
            $errors[] = ImportError::csvStructure("Empty header, affects columns: $columnList");
        }

        // Validate headers match expected fields in mapping
        $expectedFields = $mapping->csvFields;
        $missingFields = array_diff($expectedFields, $headers);
        if (!empty($missingFields)) {
            $errors[] = ImportError::csvStructure('CSV is missing expected columns: ' . implode(', ', $missingFields));
        }

        return empty($errors)
            ? ValidationResult::success()
            : ValidationResult::failure($errors);
    }
}
