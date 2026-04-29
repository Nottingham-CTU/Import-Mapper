<?php
global $module;

/**
 * File upload endpoint for CSV import
 * Handles multipart/form-data file uploads — saves the CSV and queues an async import job.
 */

// Set JSON content-type header for all responses
header('Content-Type: application/json; charset=UTF-8');

try {
    // Check permissions before processing upload
    if (!$module->permissionService->canImport()) {
        http_response_code(403);
        throw new Exception('You do not have permission to perform imports');
    }

    // Check if a file was uploaded
    if (!isset($_FILES['csv_file'])) {
        http_response_code(400);
        throw new Exception('No file uploaded');
    }

    // Check for upload errors
    $fileInfo = $_FILES['csv_file'];
    if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
        ];
        $errorMsg = $errorMessages[$fileInfo['error']] ?? 'Unknown upload error';
        http_response_code(400);
        throw new Exception($errorMsg);
    }

    // Validate file extension
    $allowedExtensions = ['csv', 'txt'];
    $filename = $fileInfo['name'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        http_response_code(400);
        throw new Exception('Invalid file type. Only CSV files are allowed.');
    }

    // Validate MIME type
    $allowedMimeTypes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileInfo['tmp_name']);
    if (!in_array($mimeType, $allowedMimeTypes)) {
        http_response_code(400);
        throw new Exception('Invalid file MIME type. Expected CSV format.');
    }

    // Validate file size (10MB limit)
    $maxSize = 10 * 1024 * 1024; // 10MB in bytes
    if ($fileInfo['size'] > $maxSize) {
        http_response_code(400);
        throw new Exception('File size exceeds 10MB limit.');
    }

    // Get and sanitize mapping ID
    $mappingId = $_POST['mapping_id'] ?? '';
    $mappingId = trim($mappingId);
    if (empty($mappingId)) {
        http_response_code(400);
        throw new Exception('Missing mapping ID');
    }

    // Queue the import job
    $result = $module->importJobManager->queueImportJob($mappingId, $fileInfo['tmp_name']);

    // Send successful JSON response
    http_response_code(200);
    echo json_encode($result);
} catch (Exception $e) {
    // Use status code set before exception, or default to 500
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'errors' => [$e->getMessage()]
    ]);
}
