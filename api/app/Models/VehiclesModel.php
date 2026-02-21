<?php

namespace App\Models;

use CodeIgniter\Model;

class VehiclesModel extends Model
{
    protected $table      = 'vehicles';
    protected $primaryKey = 'vehicle_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'vehicle_id',
        'vehicle_name',
        'vehicle_type',
        'vehicle_number',
        'rc_number',
        'vendor_id',
        'imei_number',
        'chassis_number',
        'gps_device_id',
        'registration_date',
        'status',
        'created_at',
        'updated_at',
    ];
}
