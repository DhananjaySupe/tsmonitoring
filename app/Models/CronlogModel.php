<?php

namespace App\Models;

use CodeIgniter\Model;

class CronlogModel extends Model
{
    protected $table      = 'cron_log';
    protected $primaryKey = 'cron_log_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'cron_log_id',
        'job_name',
        'executed_at',
        'status',
        'message',
        'details',
        'created_at',
    ];

    /**
     * Log a cron execution. Insert outside transaction so log is written even if job rolled back.
     *
     * @param string       $jobName  e.g. 'archive_inspections', 'build_inspection_summary'
     * @param string       $status   'success' or 'failed'
     * @param string|null  $message  Short message
     * @param array|string|null $details Optional JSON-serializable payload
     * @return int|bool Insert ID or false
     */
    public function logExecution(string $jobName, string $status, ?string $message = null, $details = null)
    {
        $data = [
            'job_name'    => $jobName,
            'executed_at' => date('Y-m-d H:i:s'),
            'status'      => $status,
            'message'     => $message,
        ];
        if ($details !== null) {
            $data['details'] = is_string($details) ? $details : json_encode($details);
        }
        return $this->insert($data);
    }
}
