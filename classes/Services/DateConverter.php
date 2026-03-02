<?php

namespace Nottingham\ImportMapper\Services;

use DateTime;

/**
 * Service for converting date strings from various formats to REDCap format
 */
final readonly class DateConverter
{
    /**
     * Convert date from CSV format to REDCap format (YYYY-MM-DD)
     *
     * @param string $value Date value from CSV
     * @param string $csvFormat CSV date format (MDY, DMY, YMD)
     * @return string|null Converted date, or null if conversion fails
     */
    public function convert(string $value, string $csvFormat): ?string
    {
        $value = trim($value);

        // Empty values pass through
        if ($value === '') {
            return '';
        }

        // Parse date
        $dateTime = $this->parseDate($value, $csvFormat);
        if ($dateTime === null) {
            return null; // Parse failed
        }

        // Always format as YYYY-MM-DD because that's what REDCap::saveData() required
        return $dateTime->format('Y-m-d');
    }

    /**
     * Parse date according to CSV format
     *
     * @param string $value Date string to parse
     * @param string $csvFormat CSV date format (MDY, DMY, YMD)
     * @return DateTime|null Parsed DateTime object, or null if parsing fails
     */
    private function parseDate(string $value, string $csvFormat): ?DateTime
    {
        // Format mappings
        $formatMap = [
            'MDY' => ['m/d/Y', 'n/j/Y', 'm-d-Y', 'n-j-Y'],
            'DMY' => ['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y'],
            'YMD' => ['Y/m/d', 'Y/n/j', 'Y-m-d', 'Y-n-j'],
        ];

        $formats = $formatMap[$csvFormat] ?? [];

        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $value);
            if ($dateTime !== false) {
                $dateTime->setTime(0, 0);
                return $dateTime;
            }
        }

        return null;
    }
}
