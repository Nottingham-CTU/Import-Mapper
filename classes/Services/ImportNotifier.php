<?php

namespace Nottingham\ImportMapper\Services;

use Message;
use Nottingham\ImportMapper\Models\ImportLogEntry;
use REDCap;

/**
 * Sends email notifications after an import job completes.
 */
final readonly class ImportNotifier
{
    /**
     * Send an import completion/failure notification email.
     *
     * @param array $emails List of recipient email addresses
     * @param ImportLogEntry $entry log entry for the job
     * @param array $errors Error objects from the import result
     * @param array $warnings Warning strings from the import result
     * @param int $projectId Project ID (passed to REDCap::email)
     */
    public function notify(
        array          $emails,
        ImportLogEntry $entry,
        array          $errors,
        array          $warnings,
        int            $projectId
    ): void {
        $to = implode(',', $emails);

        if ($entry->status === 'failed') {
            $subject = "Import Mapper: import failed — $entry->mappingName";
        } elseif ($entry->status === 'completed' && $entry->outcome === 'errors') {
            $subject = "Import Mapper: import completed with errors — $entry->mappingName";
        } elseif ($entry->status === 'completed' && $entry->outcome === 'warnings') {
            $subject = "Import Mapper: import completed with warnings — $entry->mappingName";
        } elseif ($entry->status === 'cancelled') {
            $subject = "Import Mapper: import cancelled — $entry->mappingName";
        } else {
            $subject = "Import Mapper: import complete — $entry->mappingName";
        }

        $body = $this->buildBody($entry, $errors, $warnings, $projectId);

        REDCap::email($to, Message::useDoNotReply($GLOBALS['project_contact_email']), $subject, $body, '', '', 'Import Mapper', [], $projectId);
    }

    private function buildBody(
        ImportLogEntry $entry,
        array          $errors,
        array          $warnings,
        int            $projectId
    ): string {
        if ($entry->status === 'failed') {
            $statusText = 'Failed';
        } elseif ($entry->status === 'completed' && $entry->outcome === 'errors') {
            $statusText = 'Completed with errors';
        } elseif ($entry->status === 'completed' && $entry->outcome === 'warnings') {
            $statusText = 'Completed with warnings';
        } elseif ($entry->status === 'cancelled') {
            $statusText = 'Cancelled by user';
        } else {
            $statusText = 'Success';
        }

        $rowsDisplay = htmlspecialchars("$entry->rowsProcessed / $entry->totalRows");
        $completedAt = date('Y-m-d H:i:s');

        $thStyle = 'style="text-align:left;padding:6px 8px;border:1px solid #ddd;"';
        $tdStyle = 'style="padding:6px 8px;border:1px solid #ddd;"';
        $rowOdd  = 'style="background:#f9f9f9;"';
        $rowEven = 'style="background:#fff;"';

        $html  = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="en">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '  <meta charset="UTF-8">' . "\n";
        $html .= '  <meta name="viewport" content="width=device-width,initial-scale=1">' . "\n";
        $html .= '  <title>Import Mapper notification</title>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body style="margin:0;padding:16px;font-family:Arial,sans-serif;color:#333;background:#f5f5f5;">' . "\n";
        $html .= '<div style="max-width:600px;background:#fff;padding:20px;border-radius:4px;">' . "\n";

        $html .= '<p><strong>Status:</strong> ' . htmlspecialchars($statusText) . '</p>';

        $html .= '<table style="border-collapse:collapse;width:100%;">';
        $html .= '<tr ' . $rowOdd  . '><th ' . $thStyle . '>Mapping name</th><td ' . $tdStyle . '>' . htmlspecialchars($entry->mappingName) . '</td></tr>';
        $html .= '<tr ' . $rowEven . '><th ' . $thStyle . '>Project ID</th><td ' . $tdStyle . '>' . htmlspecialchars("$projectId") . '</td></tr>';
        $html .= '<tr ' . $rowOdd  . '><th ' . $thStyle . '>Job ID</th><td ' . $tdStyle . '>' . htmlspecialchars($entry->jobId) . '</td></tr>';
        $html .= '<tr ' . $rowEven . '><th ' . $thStyle . '>Queued</th><td ' . $tdStyle . '>' . htmlspecialchars($entry->queuedAt ?? '—') . '</td></tr>';
        $html .= '<tr ' . $rowOdd  . '><th ' . $thStyle . '>Started</th><td ' . $tdStyle . '>' . htmlspecialchars($entry->startedAt ?? '—') . '</td></tr>';
        $html .= '<tr ' . $rowEven . '><th ' . $thStyle . '>Finished</th><td ' . $tdStyle . '>' . htmlspecialchars($completedAt) . '</td></tr>';
        $html .= '<tr ' . $rowOdd  . '><th ' . $thStyle . '>Rows processed</th><td ' . $tdStyle . '>' . $rowsDisplay . '</td></tr>';
        $html .= '<tr ' . $rowEven . '><th ' . $thStyle . '>Items updated</th><td ' . $tdStyle . '>' . htmlspecialchars((string)$entry->itemsUpdated) . '</td></tr>';
        $html .= '<tr ' . $rowOdd  . '><th ' . $thStyle . '>Records updated</th><td ' . $tdStyle . '>' . htmlspecialchars((string)$entry->recordsUpdated) . '</td></tr>';
        $html .= '<tr ' . $rowOdd  . '><th ' . $thStyle . '>Username</th><td ' . $tdStyle . '>' . htmlspecialchars($entry->username) . '</td></tr>';
        $html .= '</table>';

        if (!empty($errors)) {
            $html .= '<h3 style="margin-top:16px;">Errors</h3><ul>';
            foreach ($errors as $e) {
                $parts = ['<strong>' . htmlspecialchars($this->humanOrigin($e->origin->value)) . '</strong>'];
                if ($e->rowNumber !== null) {
                    $parts[] = 'Row ' . htmlspecialchars((string)$e->rowNumber);
                }
                if ($e->csvFieldName !== null) {
                    $parts[] = 'Field: ' . htmlspecialchars($e->csvFieldName);
                }
                if ($e->csvValue !== null) {
                    $parts[] = 'Value: ' . htmlspecialchars($e->csvValue);
                }
                $parts[] = htmlspecialchars($e->message);
                $html .= '<li>' . implode(' | ', $parts) . '</li>';
            }
            $html .= '</ul>';
        }

        if (!empty($warnings)) {
            $html .= '<h3 style="margin-top:16px;">Warnings</h3><ul>';
            foreach ($warnings as $w) {
                $html .= '<li>' . htmlspecialchars((string)$w) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '<hr style="border:none;border-top:1px solid #ddd;margin-top:16px;">';
        $html .= '<p style="font-size:0.85em;color:#888;">Sent by Import Mapper</p>';
        $html .= '</div>' . "\n";
        $html .= '</body></html>';

        return $html;
    }

    private function humanOrigin(string $originValue): string
    {
        return match ($originValue) {
            'MAPPING'       => 'Mapping',
            'CSV_STRUCTURE' => 'CSV structure',
            'CSV_DATA'      => 'CSV data',
            'TRANSFORMATION'=> 'Transformation',
            'REDCAP_SAVE'   => 'REDCap save',
            default         => $originValue,
        };
    }
}
