<?php

namespace App\Controllers;

use App\Models\SanitationAssetsModel;
use App\Models\SanitationInspectionSummaryModel;
use Config\Services;

/**
 * Report for sanitation_inspection_summary with filters and group-by (asset_type, sector, vendor, date).
 * Caches result when AppConfig cache is enabled.
 * existing_registration_count from SanitationAssetsModel (cached when AppConfig cache enabled).
 */
class SanitationSummaryReport extends BaseController
{

    protected function reportCacheSuffix(array $params): string
    {
        return 'inspection_summary_report_' . md5(json_encode($params));
    }

    protected function registrationCountsCacheSuffix(array $filters, string $groupBy): string
    {
        return 'registration_counts_' . md5(json_encode($filters) . $groupBy);
    }

    /**
     * Get registration counts from SanitationAssetsModel (cached when AppConfig cache enabled).
     *
     * @param array{asset_type_id?: string, vendor_id?: string, sector_id?: string, date_from?: string, date_to?: string} $filters
     * @return array<int, int>|array<string, int>
     */
    protected function getCachedRegistrationCounts(array $filters, string $groupBy): array
    {

        if(isset($filters['date_from']) && isset($filters['date_to'])) {
           unset($filters['date_from']);
           unset($filters['date_to']);
        }

        $AppConfig    = $this->AppConfig;
        $cacheEnabled = ! empty($AppConfig->cache['enabled']);
        $cache        = null;
        $cacheKey     = null;

        if ($cacheEnabled) {
            $cache    = Services::cache();
            $cacheKey = $AppConfig->cache['prefix'] . $this->registrationCountsCacheSuffix($filters, $groupBy);
            $cached   = $cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $assetsModel = new SanitationAssetsModel();
        $data       = $assetsModel->getRegistrationCounts($filters, $groupBy);

        if ($cacheEnabled && $cache && $cacheKey && is_array($data)) {
            $cache->save($cacheKey, $data, (int) $AppConfig->cache['expiration']);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $params
     * @return array{paging: array, rows: array}
     */
    protected function getCachedReport(array $params, callable $callback): array
    {
        $AppConfig    = $this->AppConfig;
        $cacheEnabled = ! empty($AppConfig->cache['enabled']);
        $cache        = null;
        $cacheKey     = null;

        if ($cacheEnabled) {
            $cache    = Services::cache();
            $cacheKey = $AppConfig->cache['prefix'] . $this->reportCacheSuffix($params);
            $cached   = $cache->get($cacheKey);
            if (is_array($cached) && isset($cached['paging'], $cached['rows'])) {
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
     * GET inspection summary report.
     * Filters: asset_type_id, vendor_id, sector_id, date_from, date_to.
     * group_by: 'asset_type' | 'sector' | 'vendor' | 'date' | (empty = raw rows).
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

        $page       = (int) $this->getParam('page', 1);
        $length     = (int) $this->getParam('per_page', 25);
        $assetType  = $this->getParam('asset_type_id', '');
        $vendorId   = $this->getParam('vendor_id', '');
        $sectorId   = $this->getParam('sector_id', '');
        $dateFrom   = $this->getParam('date_from', '');
        $dateTo     = $this->getParam('date_to', '');
        $groupBy    = $this->getParam('group_by', '');
        $orderCol   = $this->getParam('order_by_col', 's.summary_id');
        $orderDir   = $this->getParam('order_by', 'DESC');

        $groupByNorm = strtolower(trim($groupBy));
        $mergeWithList = in_array($groupByNorm, ['sector', 'vendor', 'asset_type'], true);
        $fetchPage     = $mergeWithList ? 1 : $page;
        $fetchLength   = $mergeWithList ? 9999 : $length;

        $params = [
            'page'          => $mergeWithList ? 1 : $page,
            'per_page'      => $mergeWithList ? 9999 : $length,
            'asset_type_id' => $assetType,
            'vendor_id'     => $vendorId,
            'sector_id'     => $sectorId,
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
            'group_by'      => $groupBy,
            'order_by_col'  => $orderCol,
            'order_by'      => $orderDir,
        ];

        $filters = [
            'asset_type_id' => $assetType,
            'vendor_id'     => $vendorId,
            'sector_id'     => $sectorId,
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
        ];

        $model  = new SanitationInspectionSummaryModel();
        $result = $this->getCachedReport($params, static function () use ($model, $filters, $groupBy, $fetchPage, $fetchLength, $orderCol, $orderDir) {
            return $model->getReportData($filters, $groupBy, $fetchPage, $fetchLength, $orderCol, $orderDir);
        });

        $regCounts  = $this->getCachedRegistrationCounts($filters, $groupByNorm);
        $vendors    = getVendorsList();
        $sectors    = getSectorsList();
        $assetTypes = getAssetTypesList('SANITATION');

        $zeroRow = static function (): array {
            return [
                'summary_id'                  => null,
                'inspection_date'             => null,
                'asset_type_id'               => null,
                'sector_id'                   => null,
                'vendor_id'                   => null,
                'total_asset_reg_till_date'   => 0,
                'total_inspections'           => 0,
                'total_asset_inspections'     => 0,
                'compliant_count'             => 0,
                'non_compliant_count'         => 0,
                'partial_count'               => 0,
                'avg_compliance_score'        => null,
                'existing_registration_count' => 0,
                'asset_type_name'             => '',
                'sector_name'                 => '',
                'vendor_name'                 => '',
                'vendor_code'                 => '',
            ];
        };

        if ($groupByNorm === 'sector') {
            $bySector = [];
            foreach ($result['rows'] as $row) {
                $id = isset($row['sector_id']) ? (int) $row['sector_id'] : 0;
                $bySector[$id] = $row;
            }
            $merged = [];
            foreach ($sectors as $sectorId => $info) {
                $row = $bySector[$sectorId] ?? $zeroRow();
                $row['sector_id']   = $sectorId;
                $row['sector_name'] = $info['sector_name'];
                $row['asset_type_name'] = '';
                $row['vendor_name'] = '';
                $row['vendor_code'] = '';
                if (isset($row['asset_type_id']) && isset($assetTypes[(int) $row['asset_type_id']])) {
                    $row['asset_type_name'] = $assetTypes[(int) $row['asset_type_id']]['name'];
                }
                if (isset($row['vendor_id']) && isset($vendors[(int) $row['vendor_id']])) {
                    $row['vendor_name'] = $vendors[(int) $row['vendor_id']]['vendor_name'];
                    $row['vendor_code'] = $vendors[(int) $row['vendor_id']]['vendor_code'];
                }
                $row['total_asset_reg_till_date'] = $row['total_asset_reg_till_date'];
                $row['existing_registration_count'] = (string)$regCounts[$sectorId] ?? 0;
                $merged[] = $row;
            }
            $result['paging'] = paging($page, count($merged), $length);
            $result['rows']   = array_slice($merged, $result['paging']['offset'], $length);
            $result['paging']['remainingrecords'] = $result['paging']['totalrecords'] - $result['paging']['offset'] - count($result['rows']);
        } elseif ($groupByNorm === 'vendor') {
            $byVendor = [];
            foreach ($result['rows'] as $row) {
                $id = isset($row['vendor_id']) ? (int) $row['vendor_id'] : 0;
                $byVendor[$id] = $row;
            }
            $merged = [];
            foreach ($vendors as $vendorId => $info) {
                $row = $byVendor[$vendorId] ?? $zeroRow();
                $row['vendor_id']   = $vendorId;
                $row['vendor_name'] = $info['vendor_name'];
                $row['vendor_code'] = $info['vendor_code'];
                $row['asset_type_name'] = '';
                $row['sector_name'] = '';
                if (isset($row['asset_type_id']) && isset($assetTypes[(int) $row['asset_type_id']])) {
                    $row['asset_type_name'] = $assetTypes[(int) $row['asset_type_id']]['name'];
                }
                if (isset($row['sector_id']) && isset($sectors[(int) $row['sector_id']])) {
                    $row['sector_name'] = $sectors[(int) $row['sector_id']]['sector_name'];
                }
                $row['total_asset_reg_till_date'] = $row['total_asset_reg_till_date'];
                $row['existing_registration_count'] = (string)$regCounts[$vendorId] ?? 0;
                $merged[] = $row;
            }
            $result['paging'] = paging($page, count($merged), $length);
            $result['rows']   = array_slice($merged, $result['paging']['offset'], $length);
            $result['paging']['remainingrecords'] = $result['paging']['totalrecords'] - $result['paging']['offset'] - count($result['rows']);
        } elseif ($groupByNorm === 'asset_type') {
            $byType = [];
            foreach ($result['rows'] as $row) {
                $id = isset($row['asset_type_id']) ? (int) $row['asset_type_id'] : 0;
                $byType[$id] = $row;
            }
            $merged = [];
            foreach ($assetTypes as $typeId => $info) {
                $row = $byType[$typeId] ?? $zeroRow();
                $row['asset_type_id']   = $typeId;
                $row['asset_type_name'] = $info['name'];
                $row['sector_name'] = '';
                $row['vendor_name'] = '';
                $row['vendor_code'] = '';
                if (isset($row['sector_id']) && isset($sectors[(int) $row['sector_id']])) {
                    $row['sector_name'] = $sectors[(int) $row['sector_id']]['sector_name'];
                }
                if (isset($row['vendor_id']) && isset($vendors[(int) $row['vendor_id']])) {
                    $row['vendor_name'] = $vendors[(int) $row['vendor_id']]['vendor_name'];
                    $row['vendor_code'] = $vendors[(int) $row['vendor_id']]['vendor_code'];
                }
                $row['total_asset_reg_till_date'] = $row['total_asset_reg_till_date'];
                $row['existing_registration_count'] = (string)$regCounts[$typeId] ?? 0;
                $merged[] = $row;
            }
            $result['paging'] = paging($page, count($merged), $length);
            $result['rows']   = array_slice($merged, $result['paging']['offset'], $length);
            $result['paging']['remainingrecords'] = $result['paging']['totalrecords'] - $result['paging']['offset'] - count($result['rows']);
        } else {
            foreach ($result['rows'] as &$row) {
                if ($groupByNorm === 'date') {
                    $row['existing_registration_count'] = (string)$regCounts[$row['inspection_date'] ?? ''] ?? 0;
                } else {
                    $key = (int) ($row['asset_type_id'] ?? 0) . '|' . (int) ($row['sector_id'] ?? 0) . '|' . (int) ($row['vendor_id'] ?? 0);
                    $row['existing_registration_count'] = (string)$regCounts[$key] ?? 0;
                }
                $row['total_asset_reg_till_date'] = $row['total_asset_reg_till_date'];
                $row['asset_type_name'] = '';
                $row['sector_name']     = '';
                $row['vendor_name']     = '';
                $row['vendor_code']     = '';
                if (isset($row['asset_type_id']) && isset($assetTypes[(int) $row['asset_type_id']])) {
                    $row['asset_type_name'] = $assetTypes[(int) $row['asset_type_id']]['name'];
                }
                if (isset($row['sector_id']) && isset($sectors[(int) $row['sector_id']])) {
                    $row['sector_name'] = $sectors[(int) $row['sector_id']]['sector_name'];
                }
                if (isset($row['vendor_id']) && isset($vendors[(int) $row['vendor_id']])) {
                    $row['vendor_name'] = $vendors[(int) $row['vendor_id']]['vendor_name'];
                    $row['vendor_code'] = $vendors[(int) $row['vendor_id']]['vendor_code'];
                }
            }
            unset($row);
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput([
            'paging'  => $result['paging'],
            'summary' => $result['rows'],
        ]);
        return $this->response();
    }
}
