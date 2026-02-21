<?php namespace App\Models;

use CodeIgniter\Model;

class NotificationLogsModel extends Model
{
    protected $table      = 'notification_logs';
    protected $primaryKey = 'log_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'log_id',
        'notification_id',
        'delivery_status',
        'delivery_channel',
        'recipient',
        'sent_at',
    ];
}

