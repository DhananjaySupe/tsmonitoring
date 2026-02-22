<?php

namespace App\Controllers;

use App\Models\CronlogModel;
use App\Models\SanitationInspectionsArchiveModel;
use App\Models\SanitationInspectionsModel;
use App\Models\SanitationInspectionSummaryModel;
use App\Models\UsersModel;
use App\Models\VendorsModel;

/**
 * Cron controller: archive inspections (by submitted_at day-1), build daily summary (by inspection_date day-1),
 * and send daily incidents summary email to each vendor (user_type_id 11).
 * Protect with X-API-KEY when called via HTTP (e.g. from cron).
 * Each execution is logged to cron_log.
 */
class Cron extends BaseController
{
    private const JOB_ARCHIVE_INSPECTIONS        = 'archive_inspections';
    private const JOB_BUILD_INSPECTION_SUMMARY  = 'build_inspection_summary';
    private const JOB_SANITATION_EMAIL_TO_VENDOR = 'sanitation_email_to_vendor';
    private const VENDOR_USER_TYPE_ID          = 11;

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
     */
    public function sanitationEmailToVendor()
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

        $db        = \Config\Database::connect();
        $cronlog   = new CronlogModel();
        $usersModel = new UsersModel();
        $vendorsModel = new VendorsModel();

        $sent = 0;
        $skipped = 0;
        $errors = [];

        try {
            // Vendor users: user_type_id = 11, has email, is_active
            $vendorUsers = $usersModel
                ->select('user_id, email, full_name, vendor_id')
                ->where('user_type_id', self::VENDOR_USER_TYPE_ID)
                ->where('is_active', 1)
                ->where('email !=', '')
                ->where('email IS NOT NULL')
                ->findAll();

            if (empty($vendorUsers)) {
                $cronlog->logExecution(self::JOB_SANITATION_EMAIL_TO_VENDOR, 'success', 'No vendor users with email found.', [
                    'date' => $yesterday,
                    'sent' => 0,
                ]);
                $this->setSuccess('No vendor users with email to send to.');
                $this->setOutput(['sent' => 0, 'date' => $yesterday]);
                return $this->response();
            }

            // One email per vendor (use first user's email per vendor_id)
            $vendorToEmail = [];
            foreach ($vendorUsers as $u) {
                $vid = (int) ($u['vendor_id'] ?? 0);
                if ($vid > 0 && ! isset($vendorToEmail[$vid])) {
                    $vendorToEmail[$vid] = $u['email'];
                }
            }

            foreach ($vendorToEmail as $vendorId => $toEmail) {
                $vendorRow = $vendorsModel->select('vendor_name, vendor_code')->where('vendor_id', $vendorId)->first();
                $vendorName = is_array($vendorRow) ? ($vendorRow['vendor_name'] ?? 'Vendor #' . $vendorId) : 'Vendor #' . $vendorId;

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

                $data = [
                    'vendorName'     => $vendorName,
                    'reportDate'     => $yesterday,
                    'rows'           => $rows,
                    'totalIncidents' => $totalIncidents,
                ];

                $renderer = \Config\Services::renderer();
                $renderer->setData($data);
                $html = $renderer->render('templates/emails/vendor_incidents_summary');

                $subject = 'Daily Incidents Summary – ' . $yesterday . ' – ' . $vendorName;

                if (sendEmailNotification($toEmail, $subject, $html, $this->AppConfig)) {
                    $sent++;
                } else {
                    $skipped++;
                    $errors[] = 'Vendor ' . $vendorId . ' (' . $toEmail . ')';
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
