<?php namespace App\Models;

use CodeIgniter\Model;

class UserTypesModel extends Model
{
    protected $table      = 'user_types';
    protected $primaryKey = 'user_type_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_type_id',
        'user_type',
        'index_no',
        'status',
    ];
}

