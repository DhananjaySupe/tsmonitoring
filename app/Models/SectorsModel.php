<?php namespace App\Models;

use CodeIgniter\Model;

class SectorsModel extends Model
{
    protected $table      = 'sectors';
    protected $primaryKey = 'sector_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'sector_id',
        'sector_name',
        'sector_code',
        'boundary_coordinates',
        'created_at',
    ];
}

