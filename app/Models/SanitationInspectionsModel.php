<?php namespace App\Models;

use CodeIgniter\Model;

class SanitationInspectionsModel extends Model
{
    protected $table      = 'sanitation_inspections';
    protected $primaryKey = 'inspection_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'inspection_id',
        'allocation_id',
        'asset_id',
        'asset_type_id',
        'vendor_id',
        'sector_id',
        'circle_id',
        'shift_id',
        'swachhagrahi_id',
        'inspection_date',
        'total_questions',
        'questions_answered',
        'questions_answers_data',
        'compliance_score',
        'overall_status',
        'notes',
        'latitude',
        'longitude',
        'submitted_at',
    ];

    /**
     * Delete inspections by inspection_id list (e.g. after archiving).
     *
     * @param list<int> $inspectionIds
     * @return int Number of rows deleted
     */
    public function deleteByIds(array $inspectionIds): int
    {
        if (empty($inspectionIds)) {
            return 0;
        }
        $builder = $this->builder();
        $builder->whereIn('inspection_id', $inspectionIds);
        $builder->delete();
        return $this->db->affectedRows();
    }
}

