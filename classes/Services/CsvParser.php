<?php

namespace Nottingham\ImportMapper\Services;

use Exception;
use Nottingham\ImportMapper\Models\ImportData;
use Nottingham\ImportMapper\Models\ImportDataRow;

/**
 * Parses CSV data into ImportData objects.
 */
final readonly class CsvParser
{

    /**
     * Parse CSV content that is already in memory as a string
     *
     * Row objects are only allocated for the [$offset, $offset+$limit) window, so
     * offset/limit can be used to process a subset without allocating the full dataset.
     * Omit both to parse the entire content.
     *
     * Headers and totalRows always reflect the complete content regardless of windowing.
     *
     * @param string $content Raw CSV content
     * @param int $offset Number of data rows to skip before the window starts (0-based)
     * @param int $limit Maximum number of data rows to return
     * @param int|null $knownTotalRows When provided, skips counting remaining rows after the window
     * @return ImportData Headers and up-to $limit rows starting at $offset; totalRows is the full content count
     */
    public function parseString(string $content, int $offset = 0, int $limit = PHP_INT_MAX, ?int $knownTotalRows = null): ImportData
    {
        // Strip UTF-8 BOM if present
        $content = ltrim($content, "\xef\xbb\xbf");

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return $this->parseStream($stream, $offset, $limit, $knownTotalRows);
    }

    /**
     * Read a CSV file directly from disk without loading it fully into memory.
     *
     * @param string $filePath Absolute path to the CSV file on disk
     * @param int $offset Number of data rows to skip before the window starts (0-based)
     * @param int $limit Maximum number of data rows to return
     * @param int|null $knownTotalRows When provided, skips counting remaining rows after the window
     * @return ImportData Headers and up-to $limit rows starting at $offset; totalRows is the full file count
     */
    public function parseFile(string $filePath, int $offset = 0, int $limit = PHP_INT_MAX, ?int $knownTotalRows = null): ImportData
    {
        $stream = fopen($filePath, 'r');
        if ($stream === false) {
            return new ImportData([], [], 0);
        }

        // Strip UTF-8 BOM if present at start of file
        $bom = fread($stream, 3);
        if ($bom !== "\xef\xbb\xbf") {
            rewind($stream);
        }

        return $this->parseStream($stream, $offset, $limit, $knownTotalRows);
    }

    /**
     * Shared implementation used by both parseString() and parseFile().
     */
    private function parseStream($stream, int $offset, int $limit, ?int $knownTotalRows): ImportData
    {
        // Read header row (skip empty rows before headers)
        $headers = [];
        while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
            if ($row === [null] || (count($row) === 1 && $row[0] === null)) {
                continue;
            }
            $headers = array_map('trim', $row);
            break;
        }

        if (empty($headers)) {
            fclose($stream);
            return new ImportData([], [], 0);
        }

        $expectedCount = count($headers);
        $dataRows = [];
        $dataIndex = 0; // 0-based index across all data rows (empty rows excluded)
        $chunkEnd = $offset + $limit;

        while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
            // Skip completely empty rows
            if ($row === [null] || (count($row) === 1 && $row[0] === null)) {
                continue;
            }

            if ($dataIndex >= $offset && $dataIndex < $chunkEnd) {
                $rowNumber = $dataIndex + 2; // +1 for header row, +1 for 1-based numbering
                $actualCount = count($row);
                if ($actualCount !== $expectedCount) {
                    $invalidReason = "Expected $expectedCount columns. Found $actualCount.";
                    $dataRows[] = new ImportDataRow($rowNumber, $headers, $row, false, $invalidReason);
                } else {
                    $dataRows[] = new ImportDataRow($rowNumber, $headers, array_map('trim', $row), true);
                }
            }

            $dataIndex++;

            // If we have a known total and we've finished the chunk window, stop early
            if ($knownTotalRows !== null && $dataIndex >= $chunkEnd) {
                fclose($stream);
                return new ImportData($headers, $dataRows, $knownTotalRows);
            }
        }

        fclose($stream);

        return new ImportData($headers, $dataRows, $knownTotalRows ?? $dataIndex);
    }

    /**
     * Validate CSV content and return a preview for display in the frontend.
     *
     * @param string $content Raw CSV content
     * @param array $requiredFields Optional field names that must appear as headers
     * @return array{success: bool, headers?: string[], preview?: array, totalRows?: int, errors: string[]}
     */
    public function validateAndPreview(string $content, array $requiredFields = []): array
    {
        $errors = [];

        try {
            // Parse CSV
            $importData = $this->parseString($content);
            $headers = $importData->headers;

            if (empty($headers)) {
                return [
                    'success' => false,
                    'errors' => ['CSV file is empty']
                ];
            }

            // Check for empty headers
            $emptyHeaders = [];
            foreach ($headers as $index => $header) {
                if (trim($header) === '') {
                    $emptyHeaders[] = $index + 1;
                }
            }

            if (!empty($emptyHeaders)) {
                $errors[] = 'Some column headers are empty (columns: ' . implode(', ', $emptyHeaders) . ')';
            }

            // Validate required fields
            if (!empty($requiredFields)) {
                $missingFields = array_diff($requiredFields, $headers);
                if (!empty($missingFields)) {
                    $errors[] = 'CSV is missing required fields: ' . implode(', ', $missingFields);
                }
            }

            // Get preview rows (first 5 data rows)
            $previewRows = array_slice($importData->rows, 0, 5);
            $previewData = array_map(function ($row) {
                return array_values($row->data);
            }, $previewRows);

            return [
                'success' => empty($errors),
                'headers' => $headers,
                'preview' => $previewData,
                'totalRows' => $importData->totalRows,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to parse CSV: ' . $e->getMessage()]
            ];
        }
    }
}
