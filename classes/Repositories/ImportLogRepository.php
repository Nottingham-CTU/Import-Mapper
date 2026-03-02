<?php

namespace Nottingham\ImportMapper\Repositories;

use Nottingham\ImportMapper\ImportMapper;
use Nottingham\ImportMapper\Models\ImportLogEntry;

/**
 * Data access class for logs
 */
final readonly class ImportLogRepository
{
    /**
     * @param ImportMapper $module
     */
    public function __construct(private ImportMapper $module)
    {
    }

    /**
     * @param ImportLogEntry $entry
     * @param int|null $projectId
     * @return string|null
     */
    public function save(ImportLogEntry $entry, ?int $projectId = null): ?string
    {
        $statusWithOutcome = $entry->outcome !== null
            ? "$entry->status/$entry->outcome"
            : $entry->status;

        $message = sprintf(
            'ImportMapper import %s (mapping: %s, rows: %d, items: %d, records: %d%s%s%s%s%s%s)',
            $statusWithOutcome,
            $entry->mappingName,
            $entry->rowsProcessed,
            $entry->itemsUpdated,
            $entry->recordsUpdated,
            $entry->mappingErrorCount > 0 ? ", mapping errors: $entry->mappingErrorCount" : '',
            $entry->csvStructureErrorCount > 0 ? ", csv structure errors: $entry->csvStructureErrorCount" : '',
            $entry->csvDataErrorCount > 0 ? ", csv data errors: $entry->csvDataErrorCount" : '',
            $entry->transformationErrorCount > 0 ? ", transformation errors: $entry->transformationErrorCount" : '',
            $entry->redcapSaveErrorCount > 0 ? ", redcap save errors: $entry->redcapSaveErrorCount" : '',
            $entry->warningCount > 0 ? ", warnings: $entry->warningCount" : '',
        );

        $params = [
            'mapping_id' => $entry->mappingId,
            'mapping_name' => $entry->mappingName,
            'status' => $entry->status,
            'outcome' => $entry->outcome,
            'rows_processed' => $entry->rowsProcessed,
            'rows_total' => $entry->totalRows,
            'items_updated' => $entry->itemsUpdated,
            'records_updated' => $entry->recordsUpdated,
            'mapping_error_count' => $entry->mappingErrorCount,
            'csv_structure_error_count' => $entry->csvStructureErrorCount,
            'csv_data_error_count' => $entry->csvDataErrorCount,
            'transformation_error_count' => $entry->transformationErrorCount,
            'redcap_save_error_count' => $entry->redcapSaveErrorCount,
            'warning_count' => $entry->warningCount,
            'queued_at' => $entry->queuedAt,
            'started_at' => $entry->startedAt,
        ];

        if ($entry->jobId !== '') {
            $params['job_id'] = $entry->jobId;
        }

        $params['import_log'] = 1;

        if ($projectId !== null) {
            $params['project_id'] = $projectId;
        }

        return $this->module->log($message, $params);
    }

    /**
     * @param string $logId
     * @param ImportLogEntry $entry
     * @param int|null $projectId
     * @return string|null
     */
    public function update(string $logId, ImportLogEntry $entry, ?int $projectId = null): ?string
    {
        if ($projectId !== null) {
            $this->module->removeLogs('log_id = ? AND project_id = ?', [$logId, $projectId]);
        } else {
            $this->module->removeLogs('log_id = ?', [$logId]);
        }
        return $this->save($entry, $projectId);
    }

    /**
     * @return ImportLogEntry[]
     */
    public function findAll(): array
    {
        $pseudoSql = "
            select
                log_id,
                timestamp,
                username,
                mapping_id,
                mapping_name,
                status,
                outcome,
                rows_processed,
                rows_total,
                items_updated,
                records_updated,
                mapping_error_count,
                csv_structure_error_count,
                csv_data_error_count,
                transformation_error_count,
                redcap_save_error_count,
                warning_count,
                job_id,
                queued_at,
                started_at
            where import_log = ?
            order by queued_at desc
            limit 50";

        $result = $this->module->queryLogs($pseudoSql, [1]);

        $entries = [];
        while ($row = $result->fetch_assoc()) {
            $entries[] = new ImportLogEntry(
                mappingId: (string)($row['mapping_id']),
                mappingName: (string)($row['mapping_name']),
                status: (string)($row['status']),
                outcome: $row['outcome'] ?? null,
                rowsProcessed: (int)($row['rows_processed']),
                itemsUpdated: (int)($row['items_updated'] ?? 0),
                recordsUpdated: (int)($row['records_updated'] ?? 0),
                totalRows: (int)($row['rows_total']),
                mappingErrorCount: (int)($row['mapping_error_count']),
                csvStructureErrorCount: (int)($row['csv_structure_error_count']),
                csvDataErrorCount: (int)($row['csv_data_error_count']),
                transformationErrorCount: (int)($row['transformation_error_count']),
                redcapSaveErrorCount: (int)($row['redcap_save_error_count']),
                warningCount: (int)($row['warning_count']),
                id: $row['log_id'],
                username: (string)($row['username']),
                timestamp: $row['timestamp'],
                jobId: (string)($row['job_id'] ?? ''),
                queuedAt: $row['queued_at'] ?? null,
                startedAt: $row['started_at'] ?? null,
            );
        }

        return $entries;
    }
}
