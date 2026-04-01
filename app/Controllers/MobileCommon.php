<?php

namespace App\Controllers;

use App\Models\VendorsModel;
use App\Models\UsersModel;
use App\Models\SectorsModel;
use App\Models\CirclesModel;
use App\Models\QuestionsModel;
use App\Models\ShiftsModel;
use App\Models\AssetTypesModel;
use Config\Services;

class MobileCommon extends BaseController
{
    /**
     * Generic cache wrapper for arrays.
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

    /** Vendors list/detail (joined with users: users.vendor_id = vendors.vendor_id) */
    public function vendors($id = null)
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

        $model = new UsersModel();

        // Detail by VENDOR ID (joined via vendors.user_id = users.user_id)
        if ($id !== null) {
            $vendorId = (int) $id;
            if ($vendorId < 1) {
                $this->setError('Invalid vendor id.', 400);
                return $this->response();
            }

            $data = $this->getCachedArray('mobile_vendor_' . $vendorId, static function () use ($model, $vendorId) {
                $builder = $model->builder();
                $builder->select('users.user_id, users.full_name, users.email, users.phone, users.user_type_id, users.is_active, users.created_at, users.updated_at, v.user_id, v.vendor_name, v.vendor_code, v.contact_person, v.contact_email, v.contact_phone, v.address, v.status AS vendor_status, v.created_at AS vendor_created_at');
                $builder->join('vendors v', 'v.user_id = users.user_id', 'inner');
                $builder->where('users.user_id', $vendorId);

                $row = $builder->get()->getRowArray();
                return $row ?: [];
            });

            if ($data === []) {
                $this->setError('Vendor not found.', 404);
                return $this->response();
            }

            $this->setSuccess($this->successMessage);
            $this->setOutput($data);
            return $this->response();
        }

        // List with filters/pagination
        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $status   = $this->getParam('status', '');
        $orderCol = $this->getParam('order_by_col', 'user_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $params = [
            'page'     => $page,
            'per_page' => $length,
            'keywords' => $keywords,
            'status'   => $status,
            'order_by_col' => $orderCol,
            'order_by'     => $orderDir,
        ];

        $data = $this->getCachedArray('mobile_vendors_' . md5(json_encode($params)), static function () use ($model, $page, $length, $keywords, $status, $orderCol, $orderDir) {
            $builder = $model->builder();
            $builder->select('users.user_id, users.full_name, users.email, users.phone,  users.user_type_id, users.is_active, users.created_at, users.updated_at, v.user_id, v.vendor_name, v.vendor_code, v.contact_person, v.contact_email, v.contact_phone, v.address, v.status AS vendor_status, v.created_at AS vendor_created_at');
            // Join vendors by vendor_id so we can return combined user+vendor info
            $builder->join('vendors v', 'v.user_id = users.user_id', 'inner');

            if ($keywords !== '') {
                $k = $model->db->escapeLikeString($keywords);
                $builder->groupStart()
                    ->like('v.vendor_name', $k)
                    ->orLike('v.vendor_code', $k)
                    ->orLike('v.contact_person', $k)
                    ->orLike('users.full_name', $k)
                    ->orLike('users.email', $k)
                    ->orLike('users.phone', $k)
                    ->groupEnd();
            }
            if ($status !== '') {
                $builder->where('v.status', $status);
            }

            // Map order_by_col to concrete columns
            switch ($orderCol) {
                case 'vendor_name':
                    $orderCol = 'v.vendor_name';
                    break;
                case 'vendor_code':
                    $orderCol = 'v.vendor_code';
                    break;
                case 'created_at':
                    $orderCol = 'v.created_at';
                    break;
                default:
                    $orderCol = 'v.vendor_id';
                    break;
            }

            $totalRecords = $builder->countAllResults(false);

            $paging = paging($page, $totalRecords, $length);
            $builder->orderBy($orderCol, $orderDir);
            $builder->limit($paging['length'], $paging['offset']);

            $rows = $builder->get()->getResultArray();
            $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

            return [
                'paging'  => $paging,
                'vendors' => $rows,
            ];
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput($data);
        return $this->response();
    }

    /** Sectors list/detail */
    public function sectors($id = null)
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

        $model = new SectorsModel();

        if ($id !== null) {
            $sectorId = (int) $id;
            if ($sectorId < 1) {
                $this->setError('Invalid sector id.', 400);
                return $this->response();
            }

            $data = $this->getCachedArray('mobile_sector_' . $sectorId, static function () use ($model, $sectorId) {
                $row = $model->find($sectorId);
                return $row ?: [];
            });

            if ($data === []) {
                $this->setError('Sector not found.', 404);
                return $this->response();
            }

            // Translate sector_name based on user language, if available
            if (isset($data['sector_name']) && ! empty($this->_userData['lang']) && $this->_userData['lang'] !== 'en') {
                $data['sector_name'] = translateText($data['sector_name'], $this->_userData['lang']);
            }

            $this->setSuccess($this->successMessage);
            $this->setOutput($data);
            return $this->response();
        }

        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $orderCol = $this->getParam('order_by_col', 'sector_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $params = [
            'page'     => $page,
            'per_page' => $length,
            'keywords' => $keywords,
            'order_by_col' => $orderCol,
            'order_by'     => $orderDir,
        ];

        $data = $this->getCachedArray('mobile_sectors_' . md5(json_encode($params)), static function () use ($model, $page, $length, $keywords, $orderCol, $orderDir) {
            $builder = $model->builder();
            if ($keywords !== '') {
                $k = $model->db->escapeLikeString($keywords);
                $builder->groupStart()
                    ->like('sector_name', $k)
                    ->orLike('sector_code', $k)
                    ->groupEnd();
            }

            $totalRecords = $builder->countAllResults(false);

            $paging = paging($page, $totalRecords, $length);
            $builder->orderBy($orderCol, $orderDir);
            $builder->limit($paging['length'], $paging['offset']);

            $rows = $builder->get()->getResultArray();
            $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

            return [
                'paging'  => $paging,
                'sectors' => $rows,
            ];
        });

        // Translate sector_name for list
        if (isset($data['sectors']) && is_array($data['sectors']) && ! empty($this->_userData['lang']) && $this->_userData['lang'] !== 'en') {
            foreach ($data['sectors'] as &$s) {
                if (isset($s['sector_name'])) {
                    $s['sector_name'] = translateText($s['sector_name'], $this->_userData['lang']);
                }
            }
            unset($s);
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput($data);
        return $this->response();
    }

    /** Circles list/detail */
    public function circles($id = null)
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

        $model = new CirclesModel();

        if ($id !== null) {
            $circleId = (int) $id;
            if ($circleId < 1) {
                $this->setError('Invalid circle id.', 400);
                return $this->response();
            }

            $data = $this->getCachedArray('mobile_circle_' . $circleId, static function () use ($model, $circleId) {
                $row = $model->find($circleId);
                return $row ?: [];
            });

            if ($data === []) {
                $this->setError('Circle not found.', 404);
                return $this->response();
            }

            // Translate circle_name based on user language, if available
            if (isset($data['circle_name']) && ! empty($this->_userData['lang']) && $this->_userData['lang'] !== 'en') {
                $data['circle_name'] = translateText($data['circle_name'], $this->_userData['lang']);
            }

            $this->setSuccess($this->successMessage);
            $this->setOutput($data);
            return $this->response();
        }

        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $sectorId = $this->getParam('sector_id', '');
        $orderCol = $this->getParam('order_by_col', 'circle_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $params = [
            'page'     => $page,
            'per_page' => $length,
            'keywords' => $keywords,
            'sector_id'=> $sectorId,
            'order_by_col' => $orderCol,
            'order_by'     => $orderDir,
        ];

        $data = $this->getCachedArray('mobile_circles_' . md5(json_encode($params)), static function () use ($model, $page, $length, $keywords, $sectorId, $orderCol, $orderDir) {
            $builder = $model->builder();
            if ($keywords !== '') {
                $k = $model->db->escapeLikeString($keywords);
                $builder->groupStart()
                    ->like('circle_name', $k)
                    ->orLike('circle_code', $k)
                    ->groupEnd();
            }
            if ($sectorId !== '') {
                $builder->where('sector_id', (int) $sectorId);
            }

            $totalRecords = $builder->countAllResults(false);

            $paging = paging($page, $totalRecords, $length);
            $builder->orderBy($orderCol, $orderDir);
            $builder->limit($paging['length'], $paging['offset']);

            $rows = $builder->get()->getResultArray();
            $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

            return [
                'paging'  => $paging,
                'circles' => $rows,
            ];
        });

        // Translate circle_name for list
        if (isset($data['circles']) && is_array($data['circles']) && ! empty($this->_userData['lang']) && $this->_userData['lang'] !== 'en') {
            foreach ($data['circles'] as &$c) {
                if (isset($c['circle_name'])) {
                    $c['circle_name'] = translateText($c['circle_name'], $this->_userData['lang']);
                }
            }
            unset($c);
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput($data);
        return $this->response();
    }

    /** Questions list/detail */
    public function questions($id = null)
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

        $model = new QuestionsModel();

        if ($id !== null) {
            $questionId = (int) $id;
            if ($questionId < 1) {
                $this->setError('Invalid question id.', 400);
                return $this->response();
            }

            $data = $this->getCachedArray('mobile_question_' . $questionId, static function () use ($model, $questionId) {
                $row = $model->find($questionId);
                return $row ?: [];
            });

            if ($data === []) {
                $this->setError('Question not found.', 404);
                return $this->response();
            }

            // Apply translation on question_text if lang is provided
            if (isset($data['question_text']) && $this->_userData['lang'] !== 'en') {
                $data['question_text'] = translateText($data['question_text'], $this->_userData['lang']);
            }

            $this->setSuccess($this->successMessage);
            $this->setOutput($data);
            return $this->response();
        }

        $page         = (int) $this->getParam('page', 1);
        $length       = (int) $this->getParam('per_page', 25);
        $keywords     = $this->getParam('keywords', '');
        $questionType = $this->getParam('question_type', '');
        $severity     = $this->getParam('severity', '');
        $isActive     = $this->getParam('is_active', '');
        $orderCol     = $this->getParam('order_by_col', 'question_id');
        $orderDir     = $this->getParam('order_by', 'DESC');

        $params = [
            'page'     => $page,
            'per_page' => $length,
            'keywords' => $keywords,
            'question_type' => $questionType,
            'severity'      => $severity,
            'is_active'     => $isActive,
            'order_by_col'  => $orderCol,
            'order_by'      => $orderDir,
        ];

        $data = $this->getCachedArray('mobile_questions_' . md5(json_encode($params)), static function () use ($model, $page, $length, $keywords, $questionType, $severity, $isActive, $orderCol, $orderDir) {
            $builder = $model->builder();

            if ($keywords !== '') {
                $k = $model->db->escapeLikeString($keywords);
                $builder->groupStart()
                    ->like('question_text', $k)
                    ->orLike('expected_answer', $k)
                    ->groupEnd();
            }
            if ($questionType !== '') {
                $builder->where('question_type', $questionType);
            }
            if ($severity !== '') {
                $builder->where('severity', $severity);
            }
            if ($isActive !== '') {
                $builder->where('is_active', (int) $isActive);
            }

            $totalRecords = $builder->countAllResults(false);

            $paging = paging($page, $totalRecords, $length);
            $builder->orderBy($orderCol, $orderDir);
            $builder->limit($paging['length'], $paging['offset']);

            $rows = $builder->get()->getResultArray();
            $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

            return [
                'paging'    => $paging,
                'questions' => $rows,
            ];
        });

        // Apply translation on each question_text if lang is provided
        if (isset($data['questions']) && is_array($data['questions']) && $this->_userData['lang'] !== 'en') {
            foreach ($data['questions'] as &$q) {
                if (isset($q['question_text'])) {
                    $q['question_text'] = translateText($q['question_text'], $this->_userData['lang']);
                }
            }
            unset($q);
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput($data);
        return $this->response();
    }

    /** Shifts list/detail */
    public function shifts($id = null)
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

        $model = new ShiftsModel();

        if ($id !== null) {
            $shiftId = (int) $id;
            if ($shiftId < 1) {
                $this->setError('Invalid shift id.', 400);
                return $this->response();
            }

            $data = $this->getCachedArray('mobile_shift_' . $shiftId, static function () use ($model, $shiftId) {
                $row = $model->find($shiftId);
                return $row ?: [];
            });

            if ($data === []) {
                $this->setError('Shift not found.', 404);
                return $this->response();
            }

            $this->setSuccess($this->successMessage);
            $this->setOutput($data);
            return $this->response();
        }

        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $isActive = $this->getParam('is_active', '');
        $orderCol = $this->getParam('order_by_col', 'shift_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $params = [
            'page'     => $page,
            'per_page' => $length,
            'keywords' => $keywords,
            'is_active'=> $isActive,
            'order_by_col' => $orderCol,
            'order_by'     => $orderDir,
        ];

        $data = $this->getCachedArray('mobile_shifts_' . md5(json_encode($params)), static function () use ($model, $page, $length, $keywords, $isActive, $orderCol, $orderDir) {
            $builder = $model->builder();
            if ($keywords !== '') {
                $k = $model->db->escapeLikeString($keywords);
                $builder->like('shift_name', $k);
            }
            if ($isActive !== '') {
                $builder->where('is_active', (int) $isActive);
            }

            $totalRecords = $builder->countAllResults(false);

            $paging = paging($page, $totalRecords, $length);
            $builder->orderBy($orderCol, $orderDir);
            $builder->limit($paging['length'], $paging['offset']);

            $rows = $builder->get()->getResultArray();
            $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

            return [
                'paging' => $paging,
                'shifts' => $rows,
            ];
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput($data);
        return $this->response();
    }

    /** Asset types list/detail */
    public function assetTypes($id = null)
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

        $model = new AssetTypesModel();

        if ($id !== null) {
            $assetTypeId = (int) $id;
            if ($assetTypeId < 1) {
                $this->setError('Invalid asset_type id.', 400);
                return $this->response();
            }

            $data = $this->getCachedArray('mobile_asset_type_' . $assetTypeId, static function () use ($model, $assetTypeId) {
                $row = $model->find($assetTypeId);
                return $row ?: [];
            });

            if ($data === []) {
                $this->setError('Asset type not found.', 404);
                return $this->response();
            }

            $this->setSuccess($this->successMessage);
            $this->setOutput($data);
            return $this->response();
        }

        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $type     = $this->getParam('type', '');
        $status   = $this->getParam('status', '');
        $orderCol = $this->getParam('order_by_col', 'asset_type_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $params = [
            'page'     => $page,
            'per_page' => $length,
            'keywords' => $keywords,
            'type'     => $type,
            'status'   => $status,
            'order_by_col' => $orderCol,
            'order_by'     => $orderDir,
        ];

        $data = $this->getCachedArray('mobile_asset_types_' . md5(json_encode($params)), static function () use ($model, $page, $length, $keywords, $type, $status, $orderCol, $orderDir) {
            $builder = $model->builder();

            if ($keywords !== '') {
                $k = $model->db->escapeLikeString($keywords);
                $builder->groupStart()
                    ->like('name', $k)
                    ->orLike('description', $k)
                    ->groupEnd();
            }
            if ($type !== '') {
                $builder->where('type', $type);
            }
            if ($status !== '') {
                $builder->where('status', (int) $status);
            }

            $totalRecords = $builder->countAllResults(false);

            $paging = paging($page, $totalRecords, $length);
            $builder->orderBy($orderCol, $orderDir);
            $builder->limit($paging['length'], $paging['offset']);

            $rows = $builder->get()->getResultArray();
            $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

            return [
                'paging'      => $paging,
                'asset_types' => $rows,
            ];
        });

        $this->setSuccess($this->successMessage);
        $this->setOutput($data);
        return $this->response();
    }
}

