<?php

namespace App\Models;

use CodeIgniter\Model;

class SanitationInspectionsArchiveModel extends Model
{
    protected $table      = 'sanitation_inspections_archive';
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
     * Copy rows from sanitation_inspections to archive where submitted_at is between dateStart and dateEnd.
     * Does not delete from inspections; returns inspection_ids so caller can delete.
     *
     * @return array{archived: int, ids: list<int>}
     */
    public function moveFromInspectionsBySubmittedAt(string $dateStart, string $dateEnd): array
    {
        $rows = $this->db->table('sanitation_inspections')
            ->where('submitted_at >=', $dateStart)
            ->where('submitted_at <=', $dateEnd)
            ->get()
            ->getResultArray();

        $ids = [];
        foreach ($rows as $row) {
            $this->insert($row);
            $ids[] = (int) $row['inspection_id'];
        }

        return [
            'archived' => count($ids),
            'ids'      => $ids,
        ];
    }
}
