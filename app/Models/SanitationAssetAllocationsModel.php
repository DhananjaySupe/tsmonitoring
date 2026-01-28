<?php namespace App\Models;

use CodeIgniter\Model;

class SanitationAssetAllocationsModel extends Model
{
    protected $table      = 'sanitation_asset_allocations';
    protected $primaryKey = 'allocation_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'allocation_id',
        'asset_id',
        'swachhagrahi_id',
        'shift_id',
        'allocated_by',
        'allocation_date',
        'status',
        'created_at',
    ];
}

