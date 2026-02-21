<?php

namespace App\Controllers;

use Config\Services;

class SanitationInspectionReport extends BaseController
{
    /**
     * Cache key suffix for inspection report (from filter params).
     */
    protected function reportCacheSuffix(array $params): string
    {
        return 'inspection_report_' . md5(json_encode($params));
    }

    /**
     * Get cached report or run query and cache result.
     *
     * @param array<string, mixed> $params
     * @return array{paging: array, inspections: array}
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
            if (is_array($cached) && isset($cached['paging'], $cached['inspections'])) {
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
     * GET inspection report with filters: asset_types, date (date_from, date_to), vendor, asset, GSD (swachhagrahi), shift.
     * Query result is cached by filter combination.
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

        $page        = (int) $this->getParam('page', 1);
        $length      = (int) $this->getParam('per_page', 25);
        $assetTypes  = $this->getParam('asset_types', '');
        $dateFrom    = $this->getParam('date_from', $this->getParam('date', ''));
        $dateTo      = $this->getParam('date_to', '');
        $vendorId    = $this->getParam('vendor', '');
        $assetId     = $this->getParam('asset', '');
        $gsdId       = $this->getParam('gsd', $this->getParam('swachhagrahi_id', ''));
        $shiftId     = $this->getParam('shift', '');
        $orderCol    = $this->getParam('order_by_col', 'si.inspection_id');
        $orderDir    = $this->getParam('order_by', 'DESC');

        $params = [
            'page'        => $page,
            'per_page'    => $length,
            'asset_types' => $assetTypes,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'vendor'      => $vendorId,
            'asset'       => $assetId,
            'gsd'         => $gsdId,
            'shift'       => $shiftId,
            'order_by_col'=> $orderCol,
            'order_by'    => $orderDir,
        ];

        $result = $this->getCachedReport($params, function () use ($page, $length, $assetTypes, $dateFrom, $dateTo, $vendorId, $assetId, $gsdId, $shiftId, $orderCol, $orderDir) {
            $db = \Config\Database::connect();
            $builder = $db->table('sanitation_inspections si')
                ->select(
                    'si.inspection_id, si.allocation_id, si.asset_id, si.shift_id, si.swachhagrahi_id, si.inspection_date, ' .
                    'si.total_questions, si.questions_answered, si.compliance_score, si.overall_status, si.notes, si.submitted_at, ' .
                    'sa.asset_name, sa.qr_code, sa.vendor_asset_code, sa.asset_type_id, sa.vendor_id, sa.sector_id, sa.circle_id, ' .
                    'at.name AS asset_type_name, v.vendor_name, v.vendor_code, sh.shift_name, u.full_name AS gsd_name'
                )
                ->join('sanitation_assets sa', 'si.asset_id = sa.sanitation_asset_id', 'inner')
                ->join('asset_types at', 'sa.asset_type_id = at.asset_type_id', 'left')
                ->join('vendors v', 'sa.vendor_id = v.vendor_id', 'left')
                ->join('shifts sh', 'si.shift_id = sh.shift_id', 'left')
                ->join('users u', 'si.swachhagrahi_id = u.user_id', 'left');

            if ($assetTypes !== '') {
                $ids = array_map('intval', array_filter(explode(',', $assetTypes)));
                if (! empty($ids)) {
                    $builder->whereIn('sa.asset_type_id', $ids);
                }
            }
            if ($dateFrom !== '') {
                $builder->where('si.inspection_date >=', $dateFrom);
            }
            if ($dateTo !== '') {
                $builder->where('si.inspection_date <=', $dateTo);
            }
            if ($vendorId !== '') {
                $builder->where('sa.vendor_id', (int) $vendorId);
            }
            if ($assetId !== '') {
                $builder->where('si.asset_id', (int) $assetId);
            }
            if ($gsdId !== '') {
                $builder->where('si.swachhagrahi_id', (int) $gsdId);
            }
            if ($shiftId !== '') {
                $builder->where('si.shift_id', (int) $shiftId);
            }

            $totalRecords = $builder->countAllResults(false);

            $paging = paging($page, $totalRecords, $length);
            $builder->orderBy($orderCol, $orderDir);
            $builder->limit($paging['length'], $paging['offset']);

            $rows = $builder->get()->getResultArray();
            $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

            return [
                'paging'      => $paging,
                'inspections' => $rows,
            ];
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $result['paging'], 'inspections' => $result['inspections']]);
        return $this->response();
    }
}
