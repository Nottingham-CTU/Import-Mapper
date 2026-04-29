<?php

namespace Nottingham\ImportMapper\Services;

/**
 * Service for applying regex transformations to field values
 */
final readonly class FieldRegexTransformer
{
    /**
     * Apply a regex find/replace transformation to a value
     *
     * @param string $value The input value
     * @param array|null $regexConfig Configuration with 'pattern' and 'replacement' keys
     * @return string Transformed value, or original value if regex is null or invalid
     */
    public function transform(string $value, ?array $regexConfig): string
    {
        if ($regexConfig === null) {
            return $value;
        }

        $pattern = $regexConfig['pattern'] ?? '';
        $replacement = $regexConfig['replacement'] ?? '';

        if ($pattern === '') {
            return $value;
        }

        $result = @preg_replace($pattern, $replacement, $value);

        // preg_replace returns null on error (invalid pattern)
        return $result ?? $value;
    }
}
