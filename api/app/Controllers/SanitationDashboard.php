<?php

namespace App\Controllers;

use App\Models\SanitationInspectionSummaryModel;
use Config\Services;

/**
 * Sanitation dashboard API: toilet (asset) counts with filters.
 * If date is today: use live queries (sanitation_assets + inspections). Otherwise: from sanitation_inspection_summary.
 * Summary: registered_toilets = total_asset_reg_till_date; off when compliant+non_compliant+partial=0, under otherwise.
 * Filters: date (required), vendor_id, sector_id, asset_type_id.
 * Query result is cached when AppConfig cache is enabled.
 */
class SanitationDashboard extends BaseController
{
    protected function dashboardCacheSuffix(array $params): string
    {
        return 'sanitation_dashboard_' . md5(json_encode($params));
    }

    /**
     * Run dashboard query; return cached result when AppConfig cache enabled.
     *
     * @param array<string, mixed> $params
     * @return array{total_toilets: int, registered_toilets: int, under_monitoring: int, off_monitoring: int, date: string, filters: array}
     */
    protected function getCachedDashboard(array $params, callable $callback): array
    {
        $AppConfig    = $this->AppConfig;
        $cacheEnabled = ! empty($AppConfig->cache['enabled']);
        $cache        = null;
        $cacheKey     = null;

        if ($cacheEnabled) {
            $cache    = Services::cache();
            $cacheKey = $AppConfig->cache['prefix'] . $this->dashboardCacheSuffix($params);
            $cached   = $cache->get($cacheKey);
            if (is_array($cached) && isset($cached['registered_toilets'], $cached['under_monitoring'], $cached['by_asset_type'])) {
                return $cached;
            }
        }

        $data = $callback();

        if ($cacheEnabled && $cache && $cacheKey && is_array($data)) {
            $cache->save($cacheKey, $data, (int) $AppConfig->cache['expiration']);
        }

        return $data;
    }

    /**
     * GET dashboard counts.
     * Query params: date (Y-m-d, required), vendor_id, sector_id, asset_type_id.
     */
    public function index()
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }
        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }
        if (! $this->AuthenticateToken()) {
            $this->setError($this->invalidToken, 401);
            return $this->response();
        }
        if (! $this->CheckUserTypePermissions('inspection:view')) {
            return $this->response();
        }

        $date        = $this->getParam('date', '');
        $vendorId    = $this->getParam('vendor_id', '');
        $sectorId    = $this->getParam('sector_id', '');
        $assetTypeId = $this->getParam('asset_type_id', '');

        if ($date === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1 || strtotime($date) === false) {
            $this->setError('date is required and must be Y-m-d (e.g. 2024-12-08).', 400);
            return $this->response();
        }

        $params = [
            'date'          => $date,
            'vendor_id'     => $vendorId,
            'sector_id'     => $sectorId,
            'asset_type_id' => $assetTypeId,
        ];

        $output = $this->getCachedDashboard($params, function () use ($date, $vendorId, $sectorId, $assetTypeId) {
            return $this->fetchDashboardFromLiveQueries($date, $vendorId, $sectorId, $assetTypeId);
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput($output);
        return $this->response();
    }

    /**
     * Format getDashboardCounts result for API output (add asset_type_name, source, filters).
     */
    private function formatDashboardFromSummary(array $fromSummary, string $date, string $vendorId, string $sectorId, string $assetTypeId): array
    {
        $assetTypes  = getAssetTypesList('SANITATION');
        $byAssetType = [];
        foreach ($assetTypes as $typeId => $info) {
            $row = $fromSummary['by_asset_type'][$typeId] ?? [
                'asset_type_id'      => $typeId,
                'registered_toilets' => 0,
                'under_monitoring'   => 0,
                'off_monitoring'     => 0,
            ];
            $byAssetType[] = [
                'asset_type_id'      => $typeId,
                'asset_type_name'    => $info['name'] ?? '',
                'registered_toilets' => $row['registered_toilets'],
                'under_monitoring'   => $row['under_monitoring'],
                'off_monitoring'     => $row['off_monitoring'],
            ];
        }
        return [
            'total_toilets'      => $fromSummary['registered_toilets'],
            'registered_toilets' => $fromSummary['registered_toilets'],
            'under_monitoring'   => $fromSummary['under_monitoring'],
            'off_monitoring'     => $fromSummary['off_monitoring'],
            'date'               => $date,
            'source'             => 'sanitation_inspection_summary',
            'filters'            => [
                'vendor_id'     => $vendorId !== '' ? (int) $vendorId : null,
                'sector_id'     => $sectorId !== '' ? (int) $sectorId : null,
                'asset_type_id' => $assetTypeId !== '' ? (int) $assetTypeId : null,
            ],
            'by_asset_type'      => $byAssetType,
        ];
    }

    /**
     * If date is today: live queries (assets + inspections). Otherwise: try summary first, then live.
     */
    private function fetchDashboardFromLiveQueries(string $date, string $vendorId, string $sectorId, string $assetTypeId): array
    {
        $today = date('Y-m-d');
        if ($date !== $today) {
            $summaryModel = new SanitationInspectionSummaryModel();
            $filters      = ['vendor_id' => $vendorId, 'sector_id' => $sectorId, 'asset_type_id' => $assetTypeId];
            $fromSummary  = $summaryModel->getDashboardCounts($date, $filters);
            if ($fromSummary !== null) {
                return $this->formatDashboardFromSummary($fromSummary, $date, $vendorId, $sectorId, $assetTypeId);
            }
        }

        $db = \Config\Database::connect();

        $builder = $db->table('sanitation_assets sa');
        $builder->where('sa.sanitation_asset_id >', 0);
        if ($vendorId !== '') {
            $builder->where('sa.vendor_id', (int) $vendorId);
        }
        if ($sectorId !== '') {
            $builder->where('sa.sector_id', (int) $sectorId);
        }
        if ($assetTypeId !== '') {
            $builder->where('sa.asset_type_id', (int) $assetTypeId);
        }
        $registeredToilets = (int) $builder->countAllResults();

        $whereClause = 'inspection_date = ' . $db->escape($date);
        if ($vendorId !== '') {
            $whereClause .= ' AND vendor_id = ' . (int) $vendorId;
        }
        if ($sectorId !== '') {
            $whereClause .= ' AND sector_id = ' . (int) $sectorId;
        }
        if ($assetTypeId !== '') {
            $whereClause .= ' AND asset_type_id = ' . (int) $assetTypeId;
        }
        $unionSql        = '(SELECT asset_id FROM sanitation_inspections WHERE ' . $whereClause . ') '
            . 'UNION (SELECT asset_id FROM sanitation_inspections_archive WHERE ' . $whereClause . ')';
        $underMonitoring = (int) $db->query('SELECT COUNT(DISTINCT asset_id) AS c FROM (' . $unionSql . ') u')->getRow()->c;

        $offMonitoring = $registeredToilets - $underMonitoring;
        if ($offMonitoring < 0) {
            $offMonitoring = 0;
        }

        $assetTypes = getAssetTypesList('SANITATION');

        $regByTypeBuilder = $db->table('sanitation_assets');
        $regByTypeBuilder->select('asset_type_id, COUNT(*) AS cnt')->groupBy('asset_type_id');
        if ($vendorId !== '') {
            $regByTypeBuilder->where('vendor_id', (int) $vendorId);
        }
        if ($sectorId !== '') {
            $regByTypeBuilder->where('sector_id', (int) $sectorId);
        }
        if ($assetTypeId !== '') {
            $regByTypeBuilder->where('asset_type_id', (int) $assetTypeId);
        }
        $regByTypeRows   = $regByTypeBuilder->get()->getResultArray();
        $registeredByType = [];
        foreach ($regByTypeRows as $r) {
            $registeredByType[(int) $r['asset_type_id']] = (int) $r['cnt'];
        }

        $whereClauseU = 'inspection_date = ' . $db->escape($date);
        if ($vendorId !== '') {
            $whereClauseU .= ' AND vendor_id = ' . (int) $vendorId;
        }
        if ($sectorId !== '') {
            $whereClauseU .= ' AND sector_id = ' . (int) $sectorId;
        }
        if ($assetTypeId !== '') {
            $whereClauseU .= ' AND asset_type_id = ' . (int) $assetTypeId;
        }
        $unionByType    = '(SELECT asset_type_id, asset_id FROM sanitation_inspections WHERE ' . $whereClauseU . ') '
            . 'UNION (SELECT asset_type_id, asset_id FROM sanitation_inspections_archive WHERE ' . $whereClauseU . ')';
        $underByTypeRows = $db->query('SELECT asset_type_id, COUNT(DISTINCT asset_id) AS c FROM (' . $unionByType . ') u GROUP BY asset_type_id')->getResultArray();
        $underByType = [];
        foreach ($underByTypeRows as $r) {
            $underByType[(int) $r['asset_type_id']] = (int) $r['c'];
        }

        $byAssetType = [];
        foreach ($assetTypes as $typeId => $info) {
            $reg   = $registeredByType[$typeId] ?? 0;
            $under = $underByType[$typeId] ?? 0;
            $off   = $reg - $under;
            if ($off < 0) {
                $off = 0;
            }
            $byAssetType[] = [
                'asset_type_id'      => $typeId,
                'asset_type_name'    => $info['name'] ?? '',
                'registered_toilets' => $reg,
                'under_monitoring'   => $under,
                'off_monitoring'     => $off,
            ];
        }

        return [
            'total_toilets'      => $registeredToilets,
            'registered_toilets' => $registeredToilets,
            'under_monitoring'   => $underMonitoring,
            'off_monitoring'     => $offMonitoring,
            'date'               => $date,
            'source'             => 'live',
            'filters'            => [
                'vendor_id'     => $vendorId !== '' ? (int) $vendorId : null,
                'sector_id'     => $sectorId !== '' ? (int) $sectorId : null,
                'asset_type_id' => $assetTypeId !== '' ? (int) $assetTypeId : null,
            ],
            'by_asset_type'      => $byAssetType,
        ];
    }
}
