<?php namespace App\Models;

use CodeIgniter\Model;

class UserTypePermissionsModel extends Model
{
    protected $table      = 'user_type_permissions';
    protected $primaryKey = 'permission_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'permission_id',
        'user_type_id',
        'permission',
        'can_create',
        'can_read',
        'can_update',
        'can_delete',
        'created_at',
    ];
}

