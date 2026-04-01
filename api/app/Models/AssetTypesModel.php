<?php namespace App\Models;

use CodeIgniter\Model;

class AssetTypesModel extends Model
{
    protected $table      = 'asset_types';
    protected $primaryKey = 'asset_type_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'asset_type_id',
        'type',
        'name',
        'description',
        'questions',
        'status',
    ];
}

