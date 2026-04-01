<?php namespace App\Models;

use CodeIgniter\Model;

class IncidentHistoryModel extends Model
{
    protected $table      = 'incident_history';
    protected $primaryKey = 'history_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'history_id',
        'incident_id',
        'old_status',
        'new_status',
        'changed_by',
        'comments',
        'action_taken',
        'photos',
        'changed_at',
    ];
}

