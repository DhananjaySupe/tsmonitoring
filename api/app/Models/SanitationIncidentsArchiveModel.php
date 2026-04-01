<?php

namespace App\Models;

use CodeIgniter\Model;

class SanitationIncidentsArchiveModel extends Model
{
    protected $table      = 'sanitation_incidents_archive';
    protected $primaryKey = 'incident_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'incident_id',
        'incident_code',
        'inspection_id',
        'response_id',
        'asset_id',
        'question_id',
        'reported_by',
        'resolved_by',
        'vendor_id',
        'severity',
        'description',
        'incident_status',
        'due_date',
        'resolved_date',
        'closed_date',
        'created_at',
        'updated_at',
    ];

    /**
     * Copy rows from sanitation_incidents to archive where created_at is between dateStart and dateEnd.
     * Does not delete from incidents; returns incident_ids so caller can delete.
     *
     * @return array{archived: int, ids: list<int>}
     */
    public function moveFromIncidentsByCreatedAt(string $dateStart, string $dateEnd): array
    {
        $rows = $this->db->table('sanitation_incidents')
            ->where('created_at >=', $dateStart)
            ->where('created_at <=', $dateEnd)
            ->get()
            ->getResultArray();

        $ids = [];
        foreach ($rows as $row) {
            $this->insert($row);
            $ids[] = (int) $row['incident_id'];
        }

        return [
            'archived' => count($ids),
            'ids'      => $ids,
        ];
    }
}
