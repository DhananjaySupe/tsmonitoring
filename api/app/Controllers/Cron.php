<?php

namespace App\Controllers;

use App\Models\CronlogModel;
use App\Models\SanitationIncidentsArchiveModel;
use App\Models\SanitationIncidentsModel;
use App\Models\SanitationInspectionsArchiveModel;
use App\Models\SanitationInspectionsModel;
use App\Models\SanitationInspectionSummaryModel;
use App\Models\UsersModel;

/**
 * Cron controller: archive inspections (by submitted_at day-1), archive incidents (by created_at day-1),
 * build daily summary (by inspection_date day-1), and send daily incidents summary email to each vendor (user_type_id 11).
 * Protect with X-API-KEY when called via HTTP (e.g. from cron).
 * Each execution is logged to cron_log.
 */
class Cron extends BaseController
{
    private const JOB_ARCHIVE_INSPECTIONS         = 'archive_inspections';
    private const JOB_ARCHIVE_INCIDENTS           = 'archive_incidents';
    private const JOB_BUILD_INSPECTION_SUMMARY   = 'build_inspection_summary';
    private const JOB_SANITATION_EMAIL_TO_VENDOR = 'sanitation_email_to_vendor';

    /**
     * Move rows from sanitation_inspections to sanitation_inspections_archive
     * where submitted_at is on the previous day (day-1).
     *
     * Run at 00:20:00 daily.
     */
    public function archiveInspections()
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
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
     * Move rows from sanitation_incidents to sanitation_incidents_archive
     * where created_at is on the previous day (day-1).
     *
     * Run at 00:30:00 daily.
     */
    public function archiveIncidents()
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $dateStart = $yesterday . ' 00:00:00';
        $dateEnd   = $yesterday . ' 23:59:59';

        $db = \Config\Database::connect();
        $cronlog = new CronlogModel();

        try {
            $db->transStart();

            $archiveModel = new SanitationIncidentsArchiveModel();
            $result       = $archiveModel->moveFromIncidentsByCreatedAt($dateStart, $dateEnd);

            if ($result['archived'] > 0) {
                $incidentsModel = new SanitationIncidentsModel();
                $incidentsModel->deleteByIds($result['ids']);
            }

            $db->transComplete();

            $message = 'Archived ' . $result['archived'] . ' incident(s) for ' . $yesterday;
            $cronlog->logExecution(self::JOB_ARCHIVE_INCIDENTS, 'success', $message, [
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
            $cronlog->logExecution(self::JOB_ARCHIVE_INCIDENTS, 'failed', $e->getMessage(), [
                'date'  => $yesterday,
                'error' => $e->getMessage(),
            ]);
            $this->setError('Archive incidents failed: ' . $e->getMessage(), 500);
            return $this->response();
        }
    }

    /**
     * Build and insert sanitation_inspection_summary for the previous day (day-1) by inspection_date.
     * Run at 00:05:00 daily.
     */
    public function buildInspectionSummary()
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
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

    /**
     * Send daily incidents summary email to every vendor (day-1 data, asset-type wise).
     * Recipient email is taken from users table where user_type_id = 11 (vendor user).
     * Run at 00:10:00 daily.
     */
    public function sanitationEmailToVendor()
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $dateStart = $yesterday . ' 00:00:00';
        $dateEnd   = $yesterday . ' 23:59:59';

        $db        = \Config\Database::connect();
        $cronlog   = new CronlogModel();
        $usersModel = new UsersModel();

        $sent = 0;
        $skipped = 0;
        $errors = [];

        try {
            // Vendor users: user_type_id = 11, has email, vendor_id, is_active
            $vendorUsers = $usersModel
                ->select('user_id, email, full_name, vendor_id')
                ->where('user_type_id', 11)
                ->where('is_active', 1)
                ->where('email !=', '')
                ->where('email IS NOT NULL')
                ->findAll();

            foreach ($vendorUsers as $user) {
                $vendorId   = isset($user['vendor_id']) ? (int) $user['vendor_id'] : 0;
                $toEmail    = isset($user['email']) ? trim((string) $user['email']) : '';
                $vendorName = isset($user['full_name']) ? trim((string) $user['full_name']) : 'Vendor';

                if ($toEmail === '' || $vendorId < 1) {
                    $skipped++;
                    continue;
                }

                // Day-1 incidents for this vendor, grouped by asset type
                $rows = $db->table('sanitation_incidents si')
                    ->select('at.name AS asset_type_name, COUNT(*) AS incident_count')
                    ->join('sanitation_assets sa', 'si.asset_id = sa.sanitation_asset_id', 'inner')
                    ->join('asset_types at', 'sa.asset_type_id = at.asset_type_id', 'left')
                    ->where('si.vendor_id', $vendorId)
                    ->where('si.created_at >=', $dateStart)
                    ->where('si.created_at <=', $dateEnd)
                    ->groupBy('sa.asset_type_id, at.name')
                    ->orderBy('at.name', 'ASC')
                    ->get()
                    ->getResultArray();

                $totalIncidents = 0;
                foreach ($rows as $r) {
                    $totalIncidents += (int) ($r['incident_count'] ?? 0);
                }

                // Count by incident status (OPEN, ASSIGNED, IN_PROGRESS, RESOLVED, CLOSED, REOPENED)
                $statusOrder = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'REOPENED'];
                $statusCounts = array_fill_keys($statusOrder, 0);
                $statusRows = $db->table('sanitation_incidents')
                    ->select('incident_status, COUNT(*) AS incident_count')
                    ->where('vendor_id', $vendorId)
                    ->where('created_at >=', $dateStart)
                    ->where('created_at <=', $dateEnd)
                    ->groupBy('incident_status')
                    ->get()
                    ->getResultArray();
                foreach ($statusRows as $sr) {
                    $status = isset($sr['incident_status']) ? trim((string) $sr['incident_status']) : '';
                    if ($status !== '' && isset($statusCounts[$status])) {
                        $statusCounts[$status] = (int) ($sr['incident_count'] ?? 0);
                    }
                }

                $data = [
                    'vendorName'     => $vendorName,
                    'reportDate'     => $yesterday,
                    'rows'           => $rows,
                    'totalIncidents' => $totalIncidents,
                    'statusCounts'   => $statusCounts,
                ];

                $renderer = \Config\Services::renderer();
                $renderer->setData($data);
                $html = $renderer->render('templates/emails/vendor_incidents_summary');

                $subject = 'Daily Incidents Summary – ' . $yesterday . ' – ' . $vendorName;

                $pdfPath = null;
                $tempDir = defined('WRITEPATH') ? (rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache') : (FCPATH . 'tmp');
                if (!is_dir($tempDir)) {
                    @mkdir($tempDir, 0755, true);
                }
                $pdfName = 'incidents_' . preg_replace('/[^0-9\-]/', '', $yesterday) . '_' . $vendorId . '.pdf';
                $pdfHtml = $renderer->setData($data)->render('templates/document/vendor_incidents_summary_pdf');
                try {
                    $pdf = new \App\Libraries\PDF();
                    $pdf->generate($pdfHtml, ['name' => $pdfName, 'dir' => $tempDir]);
                    $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $pdfName;
                } catch (\Throwable $e) {
                    // continue without attachment
                }

                $attachments = ($pdfPath !== null && is_file($pdfPath)) ? [$pdfPath] : [];
                if (sendEmailNotification($toEmail, $subject, $html, $this->AppConfig, $attachments)) {
                    $sent++;
                } else {
                    $skipped++;
                    $errors[] = 'Vendor ' . $vendorId . ' (' . $toEmail . ')';
                }
                if ($pdfPath !== null && is_file($pdfPath)) {
                    @unlink($pdfPath);
                }
            }

            $message = 'Sent ' . $sent . ' email(s) for ' . $yesterday . '.' . ($skipped > 0 ? ' Skipped: ' . $skipped . '.' : '');
            $cronlog->logExecution(self::JOB_SANITATION_EMAIL_TO_VENDOR, 'success', $message, [
                'date'    => $yesterday,
                'sent'    => $sent,
                'skipped' => $skipped,
                'errors'  => $errors,
            ]);

            $this->setSuccess($message);
            $this->setOutput([
                'sent'    => $sent,
                'skipped' => $skipped,
                'date'    => $yesterday,
            ]);
            return $this->response();
        } catch (\Throwable $e) {
            $cronlog->logExecution(self::JOB_SANITATION_EMAIL_TO_VENDOR, 'failed', $e->getMessage(), [
                'date'  => $yesterday,
                'error' => $e->getMessage(),
            ]);
            $this->setError('Sanitation email to vendor failed: ' . $e->getMessage(), 500);
            return $this->response();
        }
    }
}
