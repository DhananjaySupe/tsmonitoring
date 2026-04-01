<?php namespace App\Models;

use CodeIgniter\Model;

class IncidentsModel extends Model
{
    protected $table      = 'incidents';
    protected $primaryKey = 'incident_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'incident_id',
        'incident_code',
        'inspection_id',
        'response_id',
        'asset_id',
        'question_id',
        'reported_by',
        'assigned_to',
        'vendor_id',
        'severity',
        'description',
        'incident_status',
        'due_date',
        'resolved_date',
        'closed_date',
        'created_at',
        'updated_at',
    ];
}

