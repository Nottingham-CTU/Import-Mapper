<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents parsed csv data
 */
final readonly class ImportData
{
    /**
     * Constructor
     *
     * @param string[] $headers Column headers
     * @param ImportDataRow[] $rows Rows in the current chunk (or all rows if file can be processed in one chunk)
     * @param int $totalRows Total number of data rows in the file
     */
    public function __construct(
        public array $headers,
        public array $rows,
        public int   $totalRows,
    )
    {
    }
}
