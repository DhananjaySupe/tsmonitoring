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
        'photo',
        'created_by',
        'created_at',
        'updated_at',
    ];

    /**
     * Get registration counts from sanitation_assets with same filters as summary report.
     * Counts assets where DATE(created_at) <= tillDate. Grouped by groupBy for use in SanitationSummaryReport.
     *
     * @param array{asset_type_id?: string, vendor_id?: string, sector_id?: string, date_from?: string, date_to?: string} $filters
     * @param string $groupBy One of: '' (raw), 'asset_type', 'sector', 'vendor', 'date'
     * @return array<int, int>|array<string, int> For sector: [sector_id => count]. For vendor: [vendor_id => count]. For asset_type: [asset_type_id => count]. For raw/date: ['asset_type_id|sector_id|vendor_id' => count]
     */
    public function getRegistrationCounts(array $filters, string $groupBy): array
    {
        $tillDate = ! empty($filters['date_to']) ? $filters['date_to'] : date('Y-m-d');
        $builder  = $this->db->table('sanitation_assets');
        $builder->where('DATE(created_at) <=', $tillDate);
        if (! empty($filters['asset_type_id'])) {
            $builder->where('asset_type_id', (int) $filters['asset_type_id']);
        }
        if (! empty($filters['vendor_id'])) {
            $builder->where('vendor_id', (int) $filters['vendor_id']);
        }
        if (! empty($filters['sector_id'])) {
            $builder->where('sector_id', (int) $filters['sector_id']);
        }
        if (! empty($filters['date_from'])) {
            $builder->where('DATE(created_at) >=', $filters['date_from']);
        }

        $groupBy = strtolower(trim($groupBy));
        $validGroup = ['asset_type', 'sector', 'vendor', 'date'];
        if ($groupBy !== '' && in_array($groupBy, $validGroup, true)) {
            if ($groupBy === 'asset_type') {
                $builder->select('asset_type_id, COUNT(*) AS reg_count')->groupBy('asset_type_id');
            } elseif ($groupBy === 'sector') {
                $builder->select('sector_id, COUNT(*) AS reg_count')->groupBy('sector_id');
            } elseif ($groupBy === 'vendor') {
                $builder->select('vendor_id, COUNT(*) AS reg_count')->groupBy('vendor_id');
            } else {
                $builder->select('DATE(created_at) AS reg_date, COUNT(*) AS reg_count')->groupBy('DATE(created_at)');
            }
        } else {
            $builder->select('asset_type_id, sector_id, vendor_id, COUNT(*) AS reg_count')->groupBy('asset_type_id, sector_id, vendor_id');
        }

        $rows = $builder->get()->getResultArray();
        $out  = [];
        foreach ($rows as $r) {
            $count = (int) ($r['reg_count'] ?? 0);
            if ($groupBy === 'asset_type') {
                $out[(int) $r['asset_type_id']] = $count;
            } elseif ($groupBy === 'sector') {
                $out[(int) $r['sector_id']] = $count;
            } elseif ($groupBy === 'vendor') {
                $out[(int) $r['vendor_id']] = $count;
            } elseif ($groupBy === 'date') {
                $out[(string) $r['reg_date']] = $count;
            } else {
                $key = (int) $r['asset_type_id'] . '|' . (int) $r['sector_id'] . '|' . (int) $r['vendor_id'];
                $out[(string) $key] = $count;
            }
        }
        return $out;
    }
}

