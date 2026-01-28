<?php namespace App\Models;

use CodeIgniter\Model;

class QuestionsModel extends Model
{
    protected $table      = 'questions';
    protected $primaryKey = 'question_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'question_id',
        'question_text',
        'question_type',
        'options',
        'expected_answer',
        'condition_type',
        'condition_value',
        'severity',
        'is_mandatory',
        'is_photo_mandatory',
        'sequence',
        'is_active',
    ];
}

