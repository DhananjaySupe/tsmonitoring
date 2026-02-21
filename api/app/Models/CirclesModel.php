<?php namespace App\Models;

use CodeIgniter\Model;

class CirclesModel extends Model
{
    protected $table      = 'circles';
    protected $primaryKey = 'circle_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'circle_id',
        'circle_name',
        'circle_code',
        'sector_id',
        'boundary_coordinates',
        'created_at',
    ];
}

