<?php namespace App\Models;

use CodeIgniter\Model;

class InspectionsModel extends Model
{
    protected $table      = 'inspections';
    protected $primaryKey = 'inspection_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'inspection_id',
        'allocation_id',
        'asset_id',
        'shift_id',
        'swachhagrahi_id',
        'inspection_date',
        'total_questions',
        'questions_answered',
        'questions_answers_data',
        'compliance_score',
        'overall_status',
        'photos',
        'notes',
        'submitted_at',
    ];
}

