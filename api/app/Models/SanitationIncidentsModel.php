<?php namespace App\Models;

use CodeIgniter\Model;

class SanitationIncidentsModel extends Model
{
    protected $table      = 'sanitation_incidents';
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
     * Delete incidents by incident_id list (e.g. after archiving).
     *
     * @param list<int> $incidentIds
     * @return int Number of rows deleted
     */
    public function deleteByIds(array $incidentIds): int
    {
        if (empty($incidentIds)) {
            return 0;
        }
        $builder = $this->builder();
        $builder->whereIn('incident_id', $incidentIds);
        $builder->delete();
        return $this->db->affectedRows();
    }
}

