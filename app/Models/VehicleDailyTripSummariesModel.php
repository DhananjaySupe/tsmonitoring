<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleDailyTripSummariesModel extends Model
{
    protected $table      = 'vehicle_daily_trip_summaries';
    protected $primaryKey = 'summary_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'summary_id',
        'assignment_id',
        'vehicle_id',
        'route_id',
        'trip_date',
        'start_time',
        'end_time',
        'total_distance',
        'total_points_assigned',
        'total_points_visited',
        'total_points_missed',
        'total_garbage_collected',
        'avg_speed',
        'max_speed',
        'idle_time',
        'moving_time',
        'completion_percentage',
        'trip_status',
    ];
}
