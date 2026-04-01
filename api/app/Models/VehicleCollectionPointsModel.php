<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleCollectionPointsModel extends Model
{
    protected $table      = 'vehicle_collection_points';
    protected $primaryKey = 'point_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'point_id',
        'point_code',
        'point_name',
        'latitude',
        'longitude',
        'address',
        'ward_number',
        'zone',
        'point_type',
        'expected_collection_time',
        'collection_frequency',
        'status',
    ];
}
