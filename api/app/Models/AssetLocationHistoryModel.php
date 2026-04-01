<?php namespace App\Models;

use CodeIgniter\Model;

class AssetLocationHistoryModel extends Model
{
    protected $table      = 'asset_location_history';
    protected $primaryKey = 'history_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'history_id',
        'asset_id',
        'previous_latitude',
        'previous_longitude',
        'previous_sector_id',
        'previous_circle_id',
        'new_latitude',
        'new_longitude',
        'new_sector_id',
        'new_circle_id',
        'changed_by',
        'change_reason',
        'changed_at',
    ];
}

