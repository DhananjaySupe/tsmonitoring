<?php namespace App\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table      = 'attendance';
    protected $primaryKey = 'attendance_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'attendance_id',
        'swachhagrahi_id',
        'shift_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'attendance_status',
        'location_latitude',
        'location_longitude',
        'verified_by',
        'notes',
        'created_at',
    ];
}

