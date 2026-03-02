<?php

namespace Nottingham\ImportMapper\Controllers;

/**
 * Regex preview AJAX actions
 */
final readonly class RegexController
{
    /**
     * Preview the result of applying a PHP preg_replace pattern to a sample value.
     *
     * @param array $payload Request payload with pattern, replacement and value
     * @return array Response with success and result, or success=false and errors
     */
    public function preview(array $payload): array
    {
        $pattern = $payload['pattern'] ?? '';
        $replacement = $payload['replacement'] ?? '';
        $value = $payload['value'] ?? '';

        if (!is_string($pattern) || trim($pattern) === '') {
            return ['success' => false, 'errors' => ['Pattern is required']];
        }
        if (!is_string($replacement)) {
            return ['success' => false, 'errors' => ['Replacement must be a string']];
        }
        if (!is_string($value)) {
            return ['success' => false, 'errors' => ['Value must be a string']];
        }

        $result = @preg_replace($pattern, $replacement, $value);

        if ($result === null) {
            return ['success' => false, 'errors' => ['Invalid regex pattern']];
        }

        return ['success' => true, 'result' => $result];
    }
}
