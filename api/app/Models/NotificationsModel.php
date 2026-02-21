<?php namespace App\Models;

use CodeIgniter\Model;

class NotificationsModel extends Model
{
    protected $table      = 'notifications';
    protected $primaryKey = 'notification_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'notification_id',
        'user_id',
        'notification_type',
        'title',
        'message',
        'related_entity_type',
        'related_entity_id',
        'is_read',
        'priority',
        'created_at',
    ];
}

