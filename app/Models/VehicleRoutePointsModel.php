<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleRoutePointsModel extends Model
{
    protected $table      = 'vehicle_route_points';
    protected $primaryKey = 'route_point_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'route_point_id',
        'route_id',
        'point_id',
        'sequence_number',
        'estimated_arrival_time',
        'expected_stay_duration',
    ];
}
