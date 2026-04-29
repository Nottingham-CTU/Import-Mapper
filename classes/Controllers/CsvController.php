<?php

namespace Nottingham\ImportMapper\Controllers;

use Nottingham\ImportMapper\Services\CsvParser;
use Nottingham\ImportMapper\Services\PermissionService;

/**
 * CSV validation and preview AJAX actions
 */
final readonly class CsvController
{
    /**
     * Constructor
     *
     * @param CsvParser $csvParser CSV parsing service
     * @param PermissionService $permissionService Permission checking service
     */
    public function __construct(
        private CsvParser         $csvParser,
        private PermissionService $permissionService
    )
    {
    }

    /**
     * Validate CSV and return preview data
     *
     * @param array $payload Request payload with csvContent and optional requiredFields
     * @return array Response with success, headers, preview, totalRows, and errors
     */
    public function validateAndPreview(array $payload): array
    {
        if (!$this->permissionService->canImport()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to validate CSV files']
            ];
        }

        $csvContent = $payload['csvContent'] ?? '';
        $requiredFields = $payload['requiredFields'] ?? [];

        if (empty($csvContent)) {
            return [
                'success' => false,
                'errors' => ['CSV content is required']
            ];
        }

        // Validate file size (10MB limit)
        $contentSize = strlen($csvContent);
        $maxSize = 10 * 1024 * 1024;
        if ($contentSize > $maxSize) {
            return [
                'success' => false,
                'errors' => ['CSV file is too large (maximum 10MB)']
            ];
        }

        return $this->csvParser->validateAndPreview($csvContent, $requiredFields);
    }
}
