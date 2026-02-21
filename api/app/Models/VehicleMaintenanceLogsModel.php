<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleMaintenanceLogsModel extends Model
{
    protected $table      = 'vehicle_maintenance_logs';
    protected $primaryKey = 'maintenance_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'maintenance_id',
        'vehicle_id',
        'maintenance_date',
        'maintenance_type',
        'description',
        'cost',
        'next_maintenance_date',
        'vendor_id',
    ];
}
