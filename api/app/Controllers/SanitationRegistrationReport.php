<?php

namespace App\Controllers;

use App\Models\SanitationAssetsModel;
use Config\Services;

class SanitationRegistrationReport extends BaseController
{
    /**
     * Generic cache wrapper for report arrays.
     *
     * @param string   $suffix
     * @param callable $callback
     * @return array<mixed>
     */
    protected function getCachedArray(string $suffix, callable $callback): array
    {
        $AppConfig    = $this->AppConfig;
        $cacheEnabled = ! empty($AppConfig->cache['enabled']);
        $cache        = null;
        $cacheKey     = null;

        if ($cacheEnabled) {
            $cache    = Services::cache();
            $cacheKey = $AppConfig->cache['prefix'] . $suffix;
            $cached   = $cache->get($cacheKey);
            if (is_array($cached)) {
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
     * Build base assets query with common joins.
     */
    protected function baseAssetsBuilder(SanitationAssetsModel $model)
    {
        $builder = $model->builder();

        $builder->from('sanitation_assets sa')
            ->select(
                'sa.sanitation_asset_id, sa.asset_type_id, sa.qr_code, sa.asset_name, sa.short_url, sa.description, ' .
                'sa.gender, sa.vendor_id, sa.vendor_asset_code, sa.status, sa.sector_id, sa.circle_id, ' .
                'sa.latitude, sa.longitude, sa.photo, sa.created_by, sa.created_at, sa.updated_at, ' .
                'at.name AS asset_type_name, v.vendor_name, v.vendor_code, ' .
                's.sector_name, c.circle_name, u.full_name AS gsd_name'
            )
            ->join('asset_types at', 'sa.asset_type_id = at.asset_type_id', 'left')
            ->join('vendors v', 'sa.vendor_id = v.vendor_id', 'left')
            ->join('sectors s', 'sa.sector_id = s.sector_id', 'left')
            ->join('circles c', 'sa.circle_id = c.circle_id', 'left')
            ->join('users u', 'sa.created_by = u.user_id', 'left');

        return $builder;
    }

    /**
     * Apply common filters for registration-based asset reports.
     */
    protected function applyCommonFilters($builder, string $dateFrom, string $dateTo, string $assetTypeId, string $status, string $gender): void
    {
        if ($assetTypeId !== '') {
            $builder->where('sa.asset_type_id', (int) $assetTypeId);
        }
        if ($status !== '') {
            $builder->where('sa.status', $status);
        }
        if ($gender !== '') {
            $builder->where('sa.gender', $gender);
        }
        if ($dateFrom !== '') {
            $builder->where('sa.created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $builder->where('sa.created_at <=', $dateTo . ' 23:59:59');
        }
    }

    /**
     * GSD-wise registrations: assets list with gsd_name and registration_count (per GSD) on each row.
     */
    public function gsdRegistrations()
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
        if (! $this->CheckUserTypePermissions('asset:view')) {
            return $this->response();
        }

        $page       = (int) $this->getParam('page', 1);
        $length     = (int) $this->getParam('per_page', 25);
        $gsdId      = $this->getParam('gsd_id', $this->getParam('created_by', ''));
        $dateFrom   = $this->getParam('registration_date_from', $this->getParam('date_from', ''));
        $dateTo     = $this->getParam('registration_date_to', $this->getParam('date_to', ''));
        $assetType  = $this->getParam('asset_type_id', '');
        $status     = $this->getParam('status', '');
        $gender     = $this->getParam('gender', '');
        $orderCol   = $this->getParam('order_by_col', 'sa.sanitation_asset_id');
        $orderDir   = $this->getParam('order_by', 'DESC');

        $params = [
            'page'                   => $page,
            'per_page'               => $length,
            'gsd_id'                 => $gsdId,
            'registration_date_from' => $dateFrom,
            'registration_date_to'   => $dateTo,
            'asset_type_id'          => $assetType,
            'status'                 => $status,
            'gender'                 => $gender,
            'order_by_col'           => $orderCol,
            'order_by'               => $orderDir,
        ];

        $model = new SanitationAssetsModel();

        $result = $this->getCachedArray('sanitation_report_gsd_' . md5(json_encode($params)), function () use (
            $model,
            $page,
            $length,
            $gsdId,
            $dateFrom,
            $dateTo,
            $assetType,
            $status,
            $gender,
            $orderCol,
            $orderDir
        ) {
            $db = $model->db();

            $builder = $db->table('sanitation_assets sa')
                ->select('sa.created_by AS gsd_id, u.full_name AS gsd_name, COUNT(*) AS registration_count')
                ->join('users u', 'sa.created_by = u.user_id', 'left')
                ->groupBy('sa.created_by, u.full_name');
            if ($gsdId !== '') {
                $builder->where('sa.created_by', (int) $gsdId);
            }
            $this->applyCommonFilters($builder, $dateFrom, $dateTo, $assetType, $status, $gender);

            $allRows = $builder->orderBy('registration_count', $orderDir)->get()->getResultArray();
            $totalRecords = count($allRows);
            $paging = paging($page, $totalRecords, $length);
            $rows = array_slice($allRows, $paging['offset'], $paging['length']);
            $paging['remainingrecords'] = $totalRecords - $paging['offset'] - count($rows);

            return [
                'paging' => $paging,
                'assets' => $rows,
            ];
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput([
            'paging' => $result['paging'],
            'assets' => $result['assets'],
        ]);
        return $this->response();
    }

    /**
     * Vendor-wise registrations: assets list with vendor name/code and registration_count (per vendor) on each row.
     */
    public function vendorRegistrations()
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
        if (! $this->CheckUserTypePermissions('asset:view')) {
            return $this->response();
        }

        $page       = (int) $this->getParam('page', 1);
        $length     = (int) $this->getParam('per_page', 25);
        $vendorId   = $this->getParam('vendor_id', '');
        $dateFrom   = $this->getParam('registration_date_from', $this->getParam('date_from', ''));
        $dateTo     = $this->getParam('registration_date_to', $this->getParam('date_to', ''));
        $assetType  = $this->getParam('asset_type_id', '');
        $status     = $this->getParam('status', '');
        $gender     = $this->getParam('gender', '');
        $orderCol   = $this->getParam('order_by_col', 'sa.sanitation_asset_id');
        $orderDir   = $this->getParam('order_by', 'DESC');

        $params = [
            'page'                   => $page,
            'per_page'               => $length,
            'vendor_id'              => $vendorId,
            'registration_date_from' => $dateFrom,
            'registration_date_to'   => $dateTo,
            'asset_type_id'          => $assetType,
            'status'                 => $status,
            'gender'                 => $gender,
            'order_by_col'           => $orderCol,
            'order_by'               => $orderDir,
        ];

        $model = new SanitationAssetsModel();

        $result = $this->getCachedArray('sanitation_report_vendor_' . md5(json_encode($params)), function () use (
            $model,
            $page,
            $length,
            $vendorId,
            $dateFrom,
            $dateTo,
            $assetType,
            $status,
            $gender,
            $orderCol,
            $orderDir
        ) {
            $db = $model->db();

            $builder = $db->table('sanitation_assets sa')
                ->select('sa.vendor_id, v.vendor_name, v.vendor_code, COUNT(*) AS registration_count')
                ->join('vendors v', 'sa.vendor_id = v.vendor_id', 'left')
                ->groupBy('sa.vendor_id, v.vendor_name, v.vendor_code');
            if ($vendorId !== '') {
                $builder->where('sa.vendor_id', (int) $vendorId);
            }
            $this->applyCommonFilters($builder, $dateFrom, $dateTo, $assetType, $status, $gender);

            $allRows = $builder->orderBy('registration_count', $orderDir)->get()->getResultArray();
            $totalRecords = count($allRows);
            $paging = paging($page, $totalRecords, $length);
            $rows = array_slice($allRows, $paging['offset'], $paging['length']);
            $paging['remainingrecords'] = $totalRecords - $paging['offset'] - count($rows);

            return [
                'paging' => $paging,
                'assets' => $rows,
            ];
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput([
            'paging' => $result['paging'],
            'assets' => $result['assets'],
        ]);
        return $this->response();
    }

    /**
     * Sector-wise registrations: assets list with sector_name and registration_count (per sector) on each row.
     */
    public function sectorRegistrations()
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
        if (! $this->CheckUserTypePermissions('asset:view')) {
            return $this->response();
        }

        $page       = (int) $this->getParam('page', 1);
        $length     = (int) $this->getParam('per_page', 25);
        $sectorId   = $this->getParam('sector_id', '');
        $dateFrom   = $this->getParam('registration_date_from', $this->getParam('date_from', ''));
        $dateTo     = $this->getParam('registration_date_to', $this->getParam('date_to', ''));
        $assetType  = $this->getParam('asset_type_id', '');
        $status     = $this->getParam('status', '');
        $gender     = $this->getParam('gender', '');
        $orderCol   = $this->getParam('order_by_col', 'sa.sanitation_asset_id');
        $orderDir   = $this->getParam('order_by', 'DESC');

        $params = [
            'page'                   => $page,
            'per_page'               => $length,
            'sector_id'              => $sectorId,
            'registration_date_from' => $dateFrom,
            'registration_date_to'   => $dateTo,
            'asset_type_id'          => $assetType,
            'status'                 => $status,
            'gender'                 => $gender,
            'order_by_col'           => $orderCol,
            'order_by'               => $orderDir,
        ];

        $model = new SanitationAssetsModel();

        $result = $this->getCachedArray('sanitation_report_sector_' . md5(json_encode($params)), function () use (
            $model,
            $page,
            $length,
            $sectorId,
            $dateFrom,
            $dateTo,
            $assetType,
            $status,
            $gender,
            $orderCol,
            $orderDir
        ) {
            $db = $model->db();

            $builder = $db->table('sanitation_assets sa')
                ->select('sa.sector_id, s.sector_name, COUNT(*) AS registration_count')
                ->join('sectors s', 'sa.sector_id = s.sector_id', 'left')
                ->groupBy('sa.sector_id, s.sector_name');
            if ($sectorId !== '') {
                $builder->where('sa.sector_id', (int) $sectorId);
            }
            $this->applyCommonFilters($builder, $dateFrom, $dateTo, $assetType, $status, $gender);

            $allRows = $builder->orderBy('registration_count', $orderDir)->get()->getResultArray();
            $totalRecords = count($allRows);
            $paging = paging($page, $totalRecords, $length);
            $rows = array_slice($allRows, $paging['offset'], $paging['length']);
            $paging['remainingrecords'] = $totalRecords - $paging['offset'] - count($rows);

            return [
                'paging' => $paging,
                'assets' => $rows,
            ];
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput([
            'paging' => $result['paging'],
            'assets' => $result['assets'],
        ]);
        return $this->response();
    }

    /**
     * Circle-wise registrations: assets list with circle_name and registration_count (per circle) on each row.
     */
    public function circleRegistrations()
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
        if (! $this->CheckUserTypePermissions('asset:view')) {
            return $this->response();
        }

        $page       = (int) $this->getParam('page', 1);
        $length     = (int) $this->getParam('per_page', 25);
        $circleId   = $this->getParam('circle_id', '');
        $dateFrom   = $this->getParam('registration_date_from', $this->getParam('date_from', ''));
        $dateTo     = $this->getParam('registration_date_to', $this->getParam('date_to', ''));
        $assetType  = $this->getParam('asset_type_id', '');
        $status     = $this->getParam('status', '');
        $gender     = $this->getParam('gender', '');
        $orderCol   = $this->getParam('order_by_col', 'sa.sanitation_asset_id');
        $orderDir   = $this->getParam('order_by', 'DESC');

        $params = [
            'page'                   => $page,
            'per_page'               => $length,
            'circle_id'              => $circleId,
            'registration_date_from' => $dateFrom,
            'registration_date_to'   => $dateTo,
            'asset_type_id'          => $assetType,
            'status'                 => $status,
            'gender'                 => $gender,
            'order_by_col'           => $orderCol,
            'order_by'               => $orderDir,
        ];

        $model = new SanitationAssetsModel();

        $result = $this->getCachedArray('sanitation_report_circle_' . md5(json_encode($params)), function () use (
            $model,
            $page,
            $length,
            $circleId,
            $dateFrom,
            $dateTo,
            $assetType,
            $status,
            $gender,
            $orderCol,
            $orderDir
        ) {
            $db = $model->db();

            $builder = $db->table('sanitation_assets sa')
                ->select('sa.circle_id, c.circle_name, COUNT(*) AS registration_count')
                ->join('circles c', 'sa.circle_id = c.circle_id', 'left')
                ->groupBy('sa.circle_id, c.circle_name');
            if ($circleId !== '') {
                $builder->where('sa.circle_id', (int) $circleId);
            }
            $this->applyCommonFilters($builder, $dateFrom, $dateTo, $assetType, $status, $gender);

            $allRows = $builder->orderBy('registration_count', $orderDir)->get()->getResultArray();
            $totalRecords = count($allRows);
            $paging = paging($page, $totalRecords, $length);
            $rows = array_slice($allRows, $paging['offset'], $paging['length']);
            $paging['remainingrecords'] = $totalRecords - $paging['offset'] - count($rows);

            return [
                'paging' => $paging,
                'assets' => $rows,
            ];
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput([
            'paging' => $result['paging'],
            'assets' => $result['assets'],
        ]);
        return $this->response();
    }
}
