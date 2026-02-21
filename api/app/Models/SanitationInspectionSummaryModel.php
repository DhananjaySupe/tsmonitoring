<?php

namespace App\Models;

use CodeIgniter\Model;

class SanitationInspectionSummaryModel extends Model
{
    protected $table      = 'sanitation_inspection_summary';
    protected $primaryKey = 'summary_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'summary_id',
        'inspection_date',
        'asset_type_id',
        'sector_id',
        'vendor_id',
        'total_asset_reg_till_date',
        'total_inspections',
        'total_asset_inspections',
        'compliant_count',
        'non_compliant_count',
        'partial_count',
        'avg_compliance_score',
        'created_at',
        'updated_at',
    ];

    /**
     * Build summary rows for the given inspection_date from sanitation_inspections + sanitation_inspections_archive.
     * Uses INSERT ... ON DUPLICATE KEY UPDATE (unique: inspection_date, asset_type_id, sector_id, vendor_id).
     *
     * @return int Affected rows (inserted + updated)
     */
    public function buildSummaryForDate(string $inspectionDate): int
    {
        $sql = "
            INSERT INTO sanitation_inspection_summary (
                inspection_date,
                asset_type_id,
                sector_id,
                vendor_id,
                total_asset_reg_till_date,
                total_inspections,
                total_asset_inspections,
                compliant_count,
                non_compliant_count,
                partial_count,
                avg_compliance_score
            )
            SELECT
                ? AS inspection_date,
                si.asset_type_id,
                si.sector_id,
                si.vendor_id,
                COALESCE(reg.asset_reg_count, 0) AS total_asset_reg_till_date,
                COUNT(*) AS total_inspections,
                COUNT(DISTINCT si.asset_id) AS total_asset_inspections,
                SUM(CASE WHEN si.overall_status = 'COMPLIANT' THEN 1 ELSE 0 END) AS compliant_count,
                SUM(CASE WHEN si.overall_status = 'NON_COMPLIANT' THEN 1 ELSE 0 END) AS non_compliant_count,
                SUM(CASE WHEN si.overall_status = 'PARTIAL' THEN 1 ELSE 0 END) AS partial_count,
                AVG(si.compliance_score) AS avg_compliance_score
            FROM (
                SELECT asset_type_id, sector_id, vendor_id, asset_id, overall_status, compliance_score
                FROM sanitation_inspections
                WHERE inspection_date = ?
                UNION ALL
                SELECT asset_type_id, sector_id, vendor_id, asset_id, overall_status, compliance_score
                FROM sanitation_inspections_archive
                WHERE inspection_date = ?
            ) si
            LEFT JOIN (
                SELECT asset_type_id, sector_id, vendor_id, COUNT(*) AS asset_reg_count
                FROM sanitation_assets
                GROUP BY asset_type_id, sector_id, vendor_id
            ) reg ON si.asset_type_id = reg.asset_type_id AND si.sector_id = reg.sector_id AND si.vendor_id = reg.vendor_id
            GROUP BY si.asset_type_id, si.sector_id, si.vendor_id
            ON DUPLICATE KEY UPDATE
                total_asset_reg_till_date = VALUES(total_asset_reg_till_date),
                total_inspections = VALUES(total_inspections),
                total_asset_inspections = VALUES(total_asset_inspections),
                compliant_count = VALUES(compliant_count),
                non_compliant_count = VALUES(non_compliant_count),
                partial_count = VALUES(partial_count),
                avg_compliance_score = VALUES(avg_compliance_score),
                updated_at = CURRENT_TIMESTAMP
        ";

        $this->db->query($sql, [$inspectionDate, $inspectionDate, $inspectionDate]);

        $affected = $this->db->affectedRows();

        $backfillSql = "
            INSERT INTO sanitation_inspection_summary (
                inspection_date,
                asset_type_id,
                sector_id,
                vendor_id,
                total_asset_reg_till_date,
                total_inspections,
                total_asset_inspections,
                compliant_count,
                non_compliant_count,
                partial_count,
                avg_compliance_score
            )
            SELECT
                ? AS inspection_date,
                asset_type_id,
                sector_id,
                vendor_id,
                COUNT(*) AS total_asset_reg_till_date,
                0 AS total_inspections,
                0 AS total_asset_inspections,
                0 AS compliant_count,
                0 AS non_compliant_count,
                0 AS partial_count,
                NULL AS avg_compliance_score
            FROM sanitation_assets
            GROUP BY asset_type_id, sector_id, vendor_id
            ON DUPLICATE KEY UPDATE
                total_asset_reg_till_date = VALUES(total_asset_reg_till_date),
                updated_at = CURRENT_TIMESTAMP
        ";
        $this->db->query($backfillSql, [$inspectionDate]);
        $affected += $this->db->affectedRows();

        return $affected;
    }

    /**
     * Get report data with filters and optional grouping. No joins; caller enriches with names via helper lists.
     *
     * @param array{asset_type_id?: string, vendor_id?: string, sector_id?: string, date_from?: string, date_to?: string} $filters
     * @param string $groupBy One of: '' (raw), 'asset_type', 'sector', 'vendor', 'date'
     * @param int $page
     * @param int $length
     * @param string $orderCol
     * @param string $orderDir
     * @return array{paging: array, rows: array}
     */
    public function getReportData(array $filters, string $groupBy, int $page, int $length, string $orderCol, string $orderDir): array
    {
        $builder = $this->db->table('sanitation_inspection_summary s');

        if (! empty($filters['asset_type_id'])) {
            $builder->where('s.asset_type_id', (int) $filters['asset_type_id']);
        }
        if (! empty($filters['vendor_id'])) {
            $builder->where('s.vendor_id', (int) $filters['vendor_id']);
        }
        if (! empty($filters['sector_id'])) {
            $builder->where('s.sector_id', (int) $filters['sector_id']);
        }
        if (! empty($filters['date_from'])) {
            $builder->where('s.inspection_date >=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $builder->where('s.inspection_date <=', $filters['date_to']);
        }

        $groupBy = strtolower(trim($groupBy));
        $validGroupBy = ['asset_type', 'sector', 'vendor', 'date'];

        if ($groupBy !== '' && in_array($groupBy, $validGroupBy, true)) {
            $aggregates = 'SUM(s.total_asset_reg_till_date) AS total_asset_reg_till_date, ' .
                'SUM(s.total_inspections) AS total_inspections, ' .
                'SUM(s.total_asset_inspections) AS total_asset_inspections, ' .
                'SUM(s.compliant_count) AS compliant_count, ' .
                'SUM(s.non_compliant_count) AS non_compliant_count, ' .
                'SUM(s.partial_count) AS partial_count, ' .
                'AVG(s.avg_compliance_score) AS avg_compliance_score';
            if ($groupBy === 'asset_type') {
                $builder->select('s.asset_type_id, ' . $aggregates)->groupBy('s.asset_type_id');
                if ($orderCol === 's.summary_id' || $orderCol === 'summary_id') {
                    $orderCol = 's.asset_type_id';
                }
            } elseif ($groupBy === 'sector') {
                $builder->select('s.sector_id, ' . $aggregates)->groupBy('s.sector_id');
                if ($orderCol === 's.summary_id' || $orderCol === 'summary_id') {
                    $orderCol = 's.sector_id';
                }
            } elseif ($groupBy === 'vendor') {
                $builder->select('s.vendor_id, ' . $aggregates)->groupBy('s.vendor_id');
                if ($orderCol === 's.summary_id' || $orderCol === 'summary_id') {
                    $orderCol = 's.vendor_id';
                }
            } else {
                $builder->select('s.inspection_date, ' . $aggregates)->groupBy('s.inspection_date');
                if ($orderCol === 's.summary_id' || $orderCol === 'summary_id') {
                    $orderCol = 's.inspection_date';
                }
            }
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        return [
            'paging' => $paging,
            'rows'   => $rows,
        ];
    }

    /**
     * Get dashboard counts from sanitation_inspection_summary for a single date.
     * registered_toilets = total_asset_reg_till_date; under_monitoring where (compliant+non_compliant+partial)>0; off_monitoring where =0.
     *
     * @param string $date Y-m-d
     * @param array{vendor_id?: string, sector_id?: string, asset_type_id?: string} $filters
     * @return array{registered_toilets: int, under_monitoring: int, off_monitoring: int, by_asset_type: array<int, array{asset_type_id: int, registered_toilets: int, under_monitoring: int, off_monitoring: int}>}|null Null if no summary rows for date
     */
    public function getDashboardCounts(string $date, array $filters): ?array
    {
        $builder = $this->db->table('sanitation_inspection_summary s');
        $builder->where('s.inspection_date', $date);
        if (! empty($filters['vendor_id'])) {
            $builder->where('s.vendor_id', (int) $filters['vendor_id']);
        }
        if (! empty($filters['sector_id'])) {
            $builder->where('s.sector_id', (int) $filters['sector_id']);
        }
        if (! empty($filters['asset_type_id'])) {
            $builder->where('s.asset_type_id', (int) $filters['asset_type_id']);
        }

        $totals = $builder->select(
            'SUM(s.total_asset_reg_till_date) AS reg, ' .
            'SUM(s.total_asset_inspections) AS under, ' .
            'SUM(CASE WHEN (s.compliant_count + s.non_compliant_count + s.partial_count) = 0 THEN s.total_asset_reg_till_date ELSE 0 END) AS off_sum'
        )->get()->getRowArray();
        if (! $totals || ($totals['reg'] ?? null) === null) {
            return null;
        }
        $registeredToilets = (int) ($totals['reg'] ?? 0);
        $underMonitoring  = (int) ($totals['under'] ?? 0);
        $offMonitoring    = (int) ($totals['off_sum'] ?? 0);

        $byBuilder = $this->db->table('sanitation_inspection_summary s');
        $byBuilder->select(
            's.asset_type_id, ' .
            'SUM(s.total_asset_reg_till_date) AS reg, ' .
            'SUM(s.total_asset_inspections) AS under, ' .
            'SUM(CASE WHEN (s.compliant_count + s.non_compliant_count + s.partial_count) = 0 THEN s.total_asset_reg_till_date ELSE 0 END) AS off_sum'
        );
        $byBuilder->where('s.inspection_date', $date);
        if (! empty($filters['vendor_id'])) {
            $byBuilder->where('s.vendor_id', (int) $filters['vendor_id']);
        }
        if (! empty($filters['sector_id'])) {
            $byBuilder->where('s.sector_id', (int) $filters['sector_id']);
        }
        if (! empty($filters['asset_type_id'])) {
            $byBuilder->where('s.asset_type_id', (int) $filters['asset_type_id']);
        }
        $byBuilder->groupBy('s.asset_type_id');
        $byRows = $byBuilder->get()->getResultArray();

        $byAssetType = [];
        foreach ($byRows as $r) {
            $typeId = (int) $r['asset_type_id'];
            $reg    = (int) ($r['reg'] ?? 0);
            $under  = (int) ($r['under'] ?? 0);
            $off    = (int) ($r['off_sum'] ?? 0);
            $byAssetType[$typeId] = [
                'asset_type_id'      => $typeId,
                'registered_toilets' => $reg,
                'under_monitoring'   => $under,
                'off_monitoring'     => $off,
            ];
        }

        return [
            'registered_toilets' => $registeredToilets,
            'under_monitoring'   => $underMonitoring,
            'off_monitoring'     => $offMonitoring,
            'by_asset_type'      => $byAssetType,
        ];
    }
}
