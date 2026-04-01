<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleGpsTrackingModel extends Model
{
    protected $table      = 'vehicle_gps_tracking';
    protected $primaryKey = 'tracking_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'tracking_id',
        'vehicle_id',
        'assignment_id',
        'latitude',
        'longitude',
        'speed',
        'direction',
        'ignition_status',
        'fuel_level',
        'odometer_reading',
        'accuracy',
        'timestamp',
    ];
}
