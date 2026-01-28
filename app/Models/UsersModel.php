<?php namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'user_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'code',
        'password_hash',
        'email',
        'phone',
        'full_name',
        'user_type_id',
        'vendor_id',
        'is_active',
        'otp',
        'otp_expiry',
        'otp_attempts',
        'created_at',
        'updated_at',
    ];
}

