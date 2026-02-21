<?php namespace App\Models;

use CodeIgniter\Model;

class VendorsModel extends Model
{
    protected $table      = 'vendors';
    protected $primaryKey = 'vendor_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'vendor_id',
        'user_id',
        'vendor_name',
        'vendor_code',
        'contact_person',
        'contact_email',
        'contact_phone',
        'address',
        'status',
        'created_at',
    ];
}

