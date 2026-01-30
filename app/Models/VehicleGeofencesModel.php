<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleGeofencesModel extends Model
{
    protected $table      = 'vehicle_geofences';
    protected $primaryKey = 'geofence_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'geofence_id',
        'point_id',
        'radius_meters',
        'is_active',
        'created_at',
    ];
}
