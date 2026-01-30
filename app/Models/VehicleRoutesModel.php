<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleRoutesModel extends Model
{
    protected $table      = 'vehicle_routes';
    protected $primaryKey = 'route_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'route_id',
        'route_code',
        'route_name',
        'zone',
        'total_points',
        'estimated_distance',
        'estimated_duration',
        'status',
        'created_at',
    ];
}
