<?php

namespace App\Controllers;

use App\Models\CronlogModel;
use App\Models\SanitationInspectionsArchiveModel;
use App\Models\SanitationInspectionsModel;
use App\Models\SanitationInspectionSummaryModel;

/**
 * Cron controller: archive inspections (by submitted_at day-1) and build daily summary (by inspection_date day-1).
 * Protect with X-API-KEY when called via HTTP (e.g. from cron).
 * Each execution is logged to cron_log.
 */
class Cron extends BaseController
{
    private const JOB_ARCHIVE_INSPECTIONS   = 'archive_inspections';
    private const JOB_BUILD_INSPECTION_SUMMARY = 'build_inspection_summary';

    /**
     * Move rows from sanitation_inspections to sanitation_inspections_archive
     * where submitted_at is on the previous day (day-1).
     */
    public function archiveInspections()
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }
        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $dateStart = $yesterday . ' 00:00:00';
        $dateEnd   = $yesterday . ' 23:59:59';

        $db = \Config\Database::connect();
        $cronlog = new CronlogModel();

        try {
            $db->transStart();

            $archiveModel = new SanitationInspectionsArchiveModel();
            $result       = $archiveModel->moveFromInspectionsBySubmittedAt($dateStart, $dateEnd);

            if ($result['archived'] > 0) {
                $inspectionsModel = new SanitationInspectionsModel();
                $inspectionsModel->deleteByIds($result['ids']);
            }

            $db->transComplete();

            $message = 'Archived ' . $result['archived'] . ' inspection(s) for ' . $yesterday;
            $cronlog->logExecution(self::JOB_ARCHIVE_INSPECTIONS, 'success', $message, [
                'archived' => $result['archived'],
                'date'     => $yesterday,
            ]);

            $this->setSuccess($message);
            $this->setOutput(['archived' => $result['archived'], 'date' => $yesterday]);
            return $this->response();
        } catch (\Throwable $e) {
            if ($db->transStatus()) {
                $db->transRollback();
            }
            $cronlog->logExecution(self::JOB_ARCHIVE_INSPECTIONS, 'failed', $e->getMessage(), [
                'date' => $yesterday,
                'error' => $e->getMessage(),
            ]);
            $this->setError('Archive failed: ' . $e->getMessage(), 500);
            return $this->response();
        }
    }

    /**
     * Build and insert sanitation_inspection_summary for the previous day (day-1) by inspection_date.
     */
    public function buildInspectionSummary()
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }
        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }

        echo $yesterday = date('Y-m-d', strtotime('-1 day'));
        $db        = \Config\Database::connect();
        $cronlog   = new CronlogModel();

        try {
            $db->transStart();

            $summaryModel = new SanitationInspectionSummaryModel();
            $inserted     = $summaryModel->buildSummaryForDate($yesterday);

            $db->transComplete();

            $message = 'Summary built for ' . $yesterday . ': ' . $inserted . ' row(s).';
            $cronlog->logExecution(self::JOB_BUILD_INSPECTION_SUMMARY, 'success', $message, [
                'summary_rows'     => $inserted,
                'inspection_date'  => $yesterday,
            ]);

            $this->setSuccess($message);
            $this->setOutput(['summary_rows' => $inserted, 'inspection_date' => $yesterday]);
            return $this->response();
        } catch (\Throwable $e) {
            if ($db->transStatus()) {
                $db->transRollback();
            }
            $cronlog->logExecution(self::JOB_BUILD_INSPECTION_SUMMARY, 'failed', $e->getMessage(), [
                'inspection_date' => $yesterday,
                'error'           => $e->getMessage(),
            ]);
            $this->setError('Build summary failed: ' . $e->getMessage(), 500);
            return $this->response();
        }
    }
}
