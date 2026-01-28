<?php namespace App\Models;

use CodeIgniter\Model;

class SanitationAssetsModel extends Model
{
    protected $table      = 'sanitation_assets';
    protected $primaryKey = 'sanitation_asset_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'sanitation_asset_id',
        'asset_type_id',
        'qr_code',
        'asset_name',
        'short_url',
        'description',
        'gender',
        'vendor_id',
        'vendor_asset_code',
        'status',
        'sector_id',
        'circle_id',
        'latitude',
        'longitude',
        'current_photo_url',
        'created_by',
        'created_at',
        'updated_at',
    ];
}

