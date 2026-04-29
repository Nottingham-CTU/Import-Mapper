<?php

namespace Nottingham\ImportMapper\Services\Validation;

/**
 * Validates field mapping transformation configuration: date formats, value mappings,
 * field combinations, and field regex.
 */
final readonly class FieldMappingTransformValidator
{
    /**
     * Validate date format configuration for field mappings.
     *
     * @param array $fieldMappings Array of field mapping data
     * @return array Array of error messages (empty if valid)
     */
    public function validateDateFormats(array $fieldMappings): array
    {
        $errors = [];
        $validFormats = ['MDY', 'DMY', 'YMD'];

        foreach ($fieldMappings as $fm) {
            $dateFormat = $fm['dateFormat'] ?? null;

            if ($dateFormat !== null && !in_array($dateFormat, $validFormats, true)) {
                $csvFieldName = $fm['csvFieldName'] ?? 'unknown field';
                $errors[] = "Invalid date format '$dateFormat' for field '$csvFieldName'. Must be one of: MDY, DMY, YMD";
            }
        }

        return $errors;
    }

    /**
     * Validate value mappings for field mappings.
     *
     * @param array $fieldMappings Array of field mapping data
     * @return array Array of error messages (empty if valid)
     */
    public function validateValueMappings(array $fieldMappings): array
    {
        $errors = [];

        foreach ($fieldMappings as $fm) {
            $valueMappings = $fm['valueMappings'] ?? null;
            $csvFieldName = $fm['csvFieldName'] ?? 'unknown field';

            if ($valueMappings !== null && !is_array($valueMappings)) {
                $errors[] = "Invalid value mappings for field '$csvFieldName'. Must be an array.";
                continue;
            }

            if ($valueMappings !== null) {
                $inputValues = [];
                foreach ($valueMappings as $index => $mapping) {
                    if (!is_array($mapping)) {
                        $errors[] = "Invalid value mapping #$index for field '$csvFieldName'. Must be an object.";
                        continue;
                    }

                    if (!isset($mapping['input']) || !is_string($mapping['input'])) {
                        $errors[] = "Value mapping #$index for field '$csvFieldName' missing 'input' value.";
                    } else {
                        $inputValues[] = $mapping['input'];
                    }

                    if (!isset($mapping['output']) || !is_string($mapping['output'])) {
                        $errors[] = "Value mapping #$index for field '$csvFieldName' missing 'output' value.";
                    }
                }

                $uniqueInputs = array_unique($inputValues);
                if (count($inputValues) !== count($uniqueInputs)) {
                    $duplicates = array_diff_assoc($inputValues, $uniqueInputs);
                    $errors[] = "Duplicate input values in value mappings for field '$csvFieldName': " . implode(', ', array_unique($duplicates));
                }
            }
        }

        return $errors;
    }

    /**
     * Validate field combination configuration for field mappings.
     *
     * @param array $fieldMappings Array of field mapping data
     * @param array $csvFields List of available CSV field names
     * @return array Array of error messages (empty if valid)
     */
    public function validateFieldCombination(array $fieldMappings, array $csvFields): array
    {
        $errors = [];

        foreach ($fieldMappings as $fm) {
            $combineFields = $fm['combineFields'] ?? null;
            $csvFieldName = $fm['csvFieldName'] ?? 'unknown field';

            if ($combineFields === null || !($combineFields['enabled'] ?? false)) {
                continue;
            }

            if (!is_array($combineFields)) {
                $errors[] = "Invalid field combination for '$csvFieldName'. Must be an object.";
                continue;
            }

            $additionalFields = $combineFields['additionalFields'] ?? null;
            $template = $combineFields['template'] ?? null;

            if (!is_array($additionalFields)) {
                $errors[] = "Field combination for '$csvFieldName' requires 'additionalFields' array.";
                continue;
            }

            if (empty($additionalFields)) {
                $errors[] = "Field combination for '$csvFieldName' requires at least one additional field.";
                continue;
            }

            $additionalFieldNames = [];
            foreach ($additionalFields as $index => $fieldEntry) {
                if (is_string($fieldEntry)) {
                    $fieldName = $fieldEntry;
                    $fieldRegex = null;
                } elseif (is_array($fieldEntry)) {
                    if (!isset($fieldEntry['fieldName']) || !is_string($fieldEntry['fieldName'])) {
                        $errors[] = "Field combination for '$csvFieldName': additional field #$index must have a 'fieldName' string.";
                        continue;
                    }
                    $fieldName = $fieldEntry['fieldName'];
                    $fieldRegex = $fieldEntry['regex'] ?? null;

                    if ($fieldRegex !== null) {
                        $regexError = $this->validateRegexConfig($fieldRegex, "Field combination for '$csvFieldName' additional field '$fieldName'");
                        if ($regexError !== null) {
                            $errors[] = $regexError;
                        }
                    }
                } else {
                    $errors[] = "Field combination for '$csvFieldName': additional field #$index must be a string or object.";
                    continue;
                }

                if (!in_array($fieldName, $csvFields, true)) {
                    $errors[] = "Field combination for '$csvFieldName': additional field '$fieldName' does not exist in CSV.";
                }

                $additionalFieldNames[] = $fieldName;
            }

            $primaryFieldRegex = $combineFields['primaryFieldRegex'] ?? null;
            if ($primaryFieldRegex !== null) {
                $regexError = $this->validateRegexConfig($primaryFieldRegex, "Field combination for '$csvFieldName' primary field regex");
                if ($regexError !== null) {
                    $errors[] = $regexError;
                }
            }

            if (count($additionalFieldNames) !== count(array_unique($additionalFieldNames))) {
                $errors[] = "Field combination for '$csvFieldName' contains duplicate additional fields.";
            }

            if (in_array($csvFieldName, $additionalFieldNames, true)) {
                $errors[] = "Field combination for '$csvFieldName': cannot combine a field with itself.";
            }

            $additionalFields = $additionalFieldNames;

            if (!is_string($template) || trim($template) === '') {
                $errors[] = "Field combination for '$csvFieldName' requires a non-empty template.";
                continue;
            }

            $totalFields = 1 + count($additionalFields);
            $hasValidPlaceholder = false;
            for ($i = 1; $i <= $totalFields; $i++) {
                if (str_contains($template, '{' . $i . '}')) {
                    $hasValidPlaceholder = true;
                    break;
                }
            }

            if (!$hasValidPlaceholder) {
                $errors[] = "Field combination template for '$csvFieldName' must contain at least one valid placeholder ({1}, {2}, etc.).";
            }
        }

        return $errors;
    }

    /**
     * Validate fieldRegex configuration for field mappings.
     *
     * @param array $fieldMappings Array of field mapping data
     * @return array Array of error messages (empty if valid)
     */
    public function validateFieldRegex(array $fieldMappings): array
    {
        $errors = [];

        foreach ($fieldMappings as $fm) {
            $fieldRegex = $fm['fieldRegex'] ?? null;
            $csvFieldName = $fm['csvFieldName'] ?? 'unknown field';

            if ($fieldRegex === null) {
                continue;
            }

            $regexError = $this->validateRegexConfig($fieldRegex, "Field regex for '$csvFieldName'");
            if ($regexError !== null) {
                $errors[] = $regexError;
            }
        }

        return $errors;
    }

    /**
     * Validate a regex config array with pattern and replacement keys.
     *
     * @param mixed $regexConfig The regex config to validate
     * @param string $context Human-readable context for error messages
     * @return string|null Error message or null if valid
     */
    private function validateRegexConfig(mixed $regexConfig, string $context): ?string
    {
        if (!is_array($regexConfig)) {
            return "$context: regex config must be an object with 'pattern' and 'replacement'.";
        }

        $pattern = $regexConfig['pattern'] ?? null;
        $replacement = $regexConfig['replacement'] ?? null;

        if (!is_string($pattern) || trim($pattern) === '') {
            return "$context: regex 'pattern' is required and must be a non-empty string.";
        }

        if (!is_string($replacement)) {
            return "$context: regex 'replacement' is required and must be a string.";
        }

        if (@preg_match($pattern, '') === false) {
            return "$context: regex pattern '$pattern' is invalid.";
        }

        return null;
    }
}
