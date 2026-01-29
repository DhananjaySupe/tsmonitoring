<?php

namespace App\Controllers;

use App\Models\SanitationAssetsModel;

class SanitationAssets extends BaseController
{
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
        if (! $this->CheckUserTypePermissions('asset:view')) {
            return $this->response();
        }

        $model       = new SanitationAssetsModel();
        $page        = (int) $this->getParam('page', 1);
        $length      = (int) $this->getParam('per_page', 25);
        $keywords    = $this->getParam('keywords', '');
        $assetTypeId = $this->getParam('asset_type_id', '');
        $status      = $this->getParam('status', '');
        $vendorId    = $this->getParam('vendor_id', '');
        $sectorId    = $this->getParam('sector_id', '');
        $circleId    = $this->getParam('circle_id', '');
        $gender      = $this->getParam('gender', '');
        $orderCol    = $this->getParam('order_by_col', 'sanitation_asset_id');
        $orderDir    = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($keywords !== '') {
            $k = $model->db->escapeLikeString($keywords);
            $builder->groupStart()
                ->like('asset_name', $k)
                ->orLike('description', $k)
                ->orLike('qr_code', $k)
                ->groupEnd();
        }
        if ($assetTypeId !== '') {
            $builder->where('asset_type_id', (int) $assetTypeId);
        }
        if ($status !== '') {
            $builder->where('status', $status);
        }
        if ($vendorId !== '') {
            $builder->where('vendor_id', (int) $vendorId);
        }
        if ($sectorId !== '') {
            $builder->where('sector_id', (int) $sectorId);
        }
        if ($circleId !== '') {
            $builder->where('circle_id', (int) $circleId);
        }
        if ($gender !== '') {
            $builder->where('gender', $gender);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'sanitation_assets' => $rows]);
        return $this->response();
    }

    public function view($id)
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

        $assetId = (int) $id;
        if ($assetId < 1) {
            $this->setError('Invalid sanitation_asset id.', 400);
            return $this->response();
        }

        $model = new SanitationAssetsModel();
        $row   = $model->find($assetId);
        if (! $row) {
            $this->setError('Sanitation asset not found.', 404);
            return $this->response();
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput($row);
        return $this->response();
    }

    public function create()
    {
        if (! $this->isPost()) {
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
        if (! $this->CheckUserTypePermissions('asset:create')) {
            return $this->response();
        }

        $assetTypeId = $this->getPost('asset_type_id', '');
        $qrCode      = $this->getPost('qr_code', '');
        $assetName   = $this->getPost('asset_name', '');
        $shortUrl    = $this->getPost('short_url', '');
        $description = $this->getPost('description', '');
        $gender      = $this->getPost('gender', '');
        $vendorId    = $this->getPost('vendor_id', '');
        $vendorCode  = $this->getPost('vendor_asset_code', '');
        $status      = $this->getPost('status', 'ACTIVE');
        $sectorId    = $this->getPost('sector_id', '');
        $circleId    = $this->getPost('circle_id', '');
        $latitude    = $this->getPost('latitude', '');
        $longitude   = $this->getPost('longitude', '');
        $photoUrl    = $this->getPost('current_photo_url', null);
        $createdBy   = $this->getPost('created_by', '');

        if ($assetTypeId === '' || $qrCode === '' || $assetName === '' || $gender === '' || $vendorId === '' || $sectorId === '' || $circleId === '' || $latitude === '' || $longitude === '' || $createdBy === '') {
            $this->setError('asset_type_id, qr_code, asset_name, gender, vendor_id, sector_id, circle_id, latitude, longitude, created_by are required.', 400);
            return $this->response();
        }

        $model = new SanitationAssetsModel();

        if ($model->where('qr_code', $qrCode)->first()) {
            $this->setError('qr_code already exists.', 409);
            return $this->response();
        }

        if ($shortUrl === '') {
            $shortUrl = $this->generateShortUrl();
        }

        $data = [
            'asset_type_id'      => (int) $assetTypeId,
            'qr_code'            => $qrCode,
            'asset_name'         => $assetName,
            'short_url'          => $shortUrl,
            'description'        => $description,
            'gender'             => $gender,
            'vendor_id'          => (int) $vendorId,
            'vendor_asset_code'  => $vendorCode,
            'status'             => $status,
            'sector_id'          => (int) $sectorId,
            'circle_id'          => (int) $circleId,
            'latitude'           => $latitude,
            'longitude'          => $longitude,
            'current_photo_url'  => $photoUrl,
            'created_by'         => (int) $createdBy,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create sanitation asset.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Sanitation asset created successfully.');
        $this->setOutput($row);
        return $this->response();
    }

    public function edit($id)
    {
        if (! $this->isPost()) {
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
        if (! $this->CheckUserTypePermissions('asset:edit')) {
            return $this->response();
        }

        $assetId = (int) $id;
        if ($assetId < 1) {
            $this->setError('Invalid sanitation_asset id.', 400);
            return $this->response();
        }

        $model = new SanitationAssetsModel();
        $row   = $model->find($assetId);
        if (! $row) {
            $this->setError('Sanitation asset not found.', 404);
            return $this->response();
        }

        $assetTypeId = $this->getPost('asset_type_id', $row['asset_type_id'] ?? '');
        $qrCode      = $this->getPost('qr_code', $row['qr_code'] ?? '');
        $assetName   = $this->getPost('asset_name', $row['asset_name'] ?? '');
        $shortUrl    = $this->getPost('short_url', $row['short_url'] ?? '');
        $description = $this->getPost('description', $row['description'] ?? '');
        $gender      = $this->getPost('gender', $row['gender'] ?? '');
        $vendorId    = $this->getPost('vendor_id', $row['vendor_id'] ?? '');
        $vendorCode  = $this->getPost('vendor_asset_code', $row['vendor_asset_code'] ?? '');
        $status      = $this->getPost('status', $row['status'] ?? 'ACTIVE');
        $sectorId    = $this->getPost('sector_id', $row['sector_id'] ?? '');
        $circleId    = $this->getPost('circle_id', $row['circle_id'] ?? '');
        $latitude    = $this->getPost('latitude', $row['latitude'] ?? '');
        $longitude   = $this->getPost('longitude', $row['longitude'] ?? '');
        $photoUrl    = $this->getPost('current_photo_url', $row['current_photo_url'] ?? null);

        if ($assetTypeId === '' || $qrCode === '' || $assetName === '' || $gender === '' || $vendorId === '' || $sectorId === '' || $circleId === '' || $latitude === '' || $longitude === '') {
            $this->setError('asset_type_id, qr_code, asset_name, gender, vendor_id, sector_id, circle_id, latitude, longitude are required.', 400);
            return $this->response();
        }

        if ($model->where('qr_code', $qrCode)->where('sanitation_asset_id !=', $assetId)->first()) {
            $this->setError('qr_code already exists.', 409);
            return $this->response();
        }

        if ($shortUrl === '') {
            $shortUrl = $row['short_url'] ?? $this->generateShortUrl();
        }

        $data = [
            'asset_type_id'      => (int) $assetTypeId,
            'qr_code'            => $qrCode,
            'asset_name'         => $assetName,
            'short_url'          => $shortUrl,
            'description'        => $description,
            'gender'             => $gender,
            'vendor_id'          => (int) $vendorId,
            'vendor_asset_code'  => $vendorCode,
            'status'             => $status,
            'sector_id'          => (int) $sectorId,
            'circle_id'          => (int) $circleId,
            'latitude'           => $latitude,
            'longitude'          => $longitude,
            'current_photo_url'  => $photoUrl,
        ];

        $model->update($assetId, $data);
        $updated = $model->find($assetId);
        $this->setSuccess('Sanitation asset updated successfully.');
        $this->setOutput($updated);
        return $this->response();
    }

    public function delete($id)
    {
        if (! $this->isPost()) {
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
        if (! $this->CheckUserTypePermissions('asset:delete')) {
            return $this->response();
        }

        $assetId = (int) $id;
        if ($assetId < 1) {
            $this->setError('Invalid sanitation_asset id.', 400);
            return $this->response();
        }

        $model = new SanitationAssetsModel();
        $row   = $model->find($assetId);
        if (! $row) {
            $this->setError('Sanitation asset not found.', 404);
            return $this->response();
        }

        $model->delete($assetId);
        $this->setSuccess('Sanitation asset deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }

    private function generateShortUrl(): string
    {
        $model = new SanitationAssetsModel();

        do {
            $code = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $exists = $model->where('short_url', $code)->first();
        } while ($exists);

        return $code;
    }
}

