<?php namespace App\Models;

use CodeIgniter\Model;

class ShiftsModel extends Model
{
    protected $table      = 'shifts';
    protected $primaryKey = 'shift_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'shift_id',
        'shift_name',
        'start_time',
        'end_time',
        'is_active',
    ];
}

