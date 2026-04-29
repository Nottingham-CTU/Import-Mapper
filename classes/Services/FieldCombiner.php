<?php

namespace Nottingham\ImportMapper\Services;

use Nottingham\ImportMapper\Models\ImportDataRow;

/**
 * Service for combining multiple CSV fields into a single value
 */
final readonly class FieldCombiner
{
    public function __construct(
        private FieldRegexTransformer $fieldRegexTransformer
    )
    {
    }

    /**
     * Combine multiple field values using a template
     *
     * @param ImportDataRow $dataRow The data row containing all CSV values
     * @param string $primaryFieldName The primary CSV field name
     * @param array|null $combineConfig Configuration
     * @return string|null Combined value, or null if combination not enabled/configured
     */
    public function combine(ImportDataRow $dataRow, string $primaryFieldName, ?array $combineConfig): ?string
    {
        // Return null if the combination not configured or not enabled
        if ($combineConfig === null || !($combineConfig['enabled'] ?? false)) {
            return null;
        }

        $additionalFields = $combineConfig['additionalFields'] ?? [];
        $template = $combineConfig['template'] ?? '';

        // If no additional fields or empty template, return null (not combining)
        if (empty($additionalFields) || trim($template) === '') {
            return null;
        }

        // Collect all source values: primary + additional
        $values = [];

        // {1} = primary field, with optional primaryFieldRegex
        $primaryValue = $dataRow->get($primaryFieldName) ?? '';
        $primaryFieldRegex = $combineConfig['primaryFieldRegex'] ?? null;
        $values[1] = $this->fieldRegexTransformer->transform($primaryValue, $primaryFieldRegex);

        // {2}, {3}, etc. = additional fields in order
        // additionalFields is now an array of objects: { fieldName, regex }
        $placeholderIndex = 2;
        foreach ($additionalFields as $fieldEntry) {
            $fieldName = $fieldEntry['fieldName'] ?? '';
            $fieldRegex = $fieldEntry['regex'] ?? null;
            $fieldValue = $dataRow->get($fieldName) ?? '';
            $values[$placeholderIndex] = $this->fieldRegexTransformer->transform($fieldValue, $fieldRegex);
            $placeholderIndex++;
        }

        // Replace placeholders in template
        $result = $template;
        foreach ($values as $index => $value) {
            $result = str_replace('{' . $index . '}', $value, $result);
        }

        return $result;
    }
}
