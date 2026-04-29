<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents the result of an import operation
 */
final readonly class ImportResult
{
    /**
     * Constructor
     *
     * @param bool $success Whether the import was successful
     * @param array $recordIds Array of record IDs that were created/updated
     * @param ImportError[] $errors Array of error messages
     * @param int $rowsProcessed Number of CSV rows processed
     * @param int $itemsUpdated Number of items saved to REDCap
     * @param array $warnings Warnings from REDCap saveData
     * @param int $totalRows Total number of rows in the CSV
     * @param bool $isLastChunk Whether this is the last chunk of data
     * @param bool $failed Whether a failure occurred
     * @param bool $cancelled Whether the user canceled the import
     */
    public function __construct(
        public bool  $success,
        public array $recordIds,
        public array $errors,
        public int   $rowsProcessed,
        public int   $itemsUpdated,
        public array $warnings = [],
        public int   $totalRows = 0,
        public bool  $isLastChunk = true,
        public bool  $failed = false,
        public bool  $cancelled = false,
    )
    {
    }

    /**
     * Create a failed import result from an array of errors
     *
     * @param array $errors Array of error messages
     * @return self
     */
    public static function fromErrors(array $errors): self
    {
        return new self(
            success: false,
            recordIds: [],
            errors: $errors,
            rowsProcessed: 0,
            itemsUpdated: 0,
            failed: true,
        );
    }

    /**
     * Create a cancelled import result
     *
     * @param ImportError[] $errors Errors accumulated before cancellation
     * @param array $warnings Warnings accumulated before cancellation
     * @return self
     */
    public static function cancelled(array $errors = [], array $warnings = []): self
    {
        return new self(
            success: false,
            recordIds: [],
            errors: $errors,
            rowsProcessed: 0,
            itemsUpdated: 0,
            warnings: $warnings,
            cancelled: true,
        );
    }
}
