<?php namespace App\Models;

use CodeIgniter\Model;

class SystemConfigModel extends Model
{
    protected $table      = 'system_config';
    protected $primaryKey = 'config_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'config_id',
        'config_key',
        'config_value',
        'description',
        'config_type',
        'is_active',
        'updated_by',
        'updated_at',
    ];
}

