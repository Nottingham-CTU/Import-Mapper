<?php

namespace Nottingham\ImportMapper\Services;

/**
 * Service for mapping CSV values to REDCap values
 */
final readonly class ValueMapper
{
    /**
     * Apply value mappings to a value
     *
     * @param string $value The value to map
     * @param array|null $valueMappings Array of ['input' => ..., 'output' => ...] mappings
     * @return string Mapped value, or original value if no mapping found
     */
    public function map(string $value, ?array $valueMappings): string
    {
        // If no mappings configured, return original value
        if (empty($valueMappings)) {
            return $value;
        }

        // Build lookup table from
        $lookup = [];
        foreach ($valueMappings as $mapping) {
            if (isset($mapping['input'], $mapping['output'])) {
                $lookup[$mapping['input']] = $mapping['output'];
            }
        }

        return $lookup[$value] ?? $value;
    }
}
