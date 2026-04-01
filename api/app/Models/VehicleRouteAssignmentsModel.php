<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleRouteAssignmentsModel extends Model
{
    protected $table      = 'vehicle_route_assignments';
    protected $primaryKey = 'assignment_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'assignment_id',
        'vehicle_id',
        'route_id',
        'assignment_date',
        'driver_id',
        'shift',
        'planned_start_time',
        'planned_end_time',
        'assignment_status',
    ];
}
