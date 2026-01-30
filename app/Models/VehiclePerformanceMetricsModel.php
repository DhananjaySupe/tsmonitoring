<?php

namespace App\Models;

use CodeIgniter\Model;

class VehiclePerformanceMetricsModel extends Model
{
    protected $table      = 'vehicle_performance_metrics';
    protected $primaryKey = 'metric_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'metric_id',
        'vehicle_id',
        'route_id',
        'metric_date',
        'metric_type',
        'metric_value',
    ];
}
