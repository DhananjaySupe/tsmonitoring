<?php

namespace App\Controllers;

use App\Models\SanitationAssetsModel;

class AssetTagging extends BaseController
{
    public function sanitationAssetTagging()
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
        if (! $this->CheckUserTypePermissions('asset-tagging:create')) {
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
        $photoUrl    = $this->getPost('photo', null);

        if ($assetTypeId === '' || $qrCode === '' || $assetName === '' || $gender === '' || $vendorId === '' || $sectorId === '' || $circleId === '' || $latitude === '' || $longitude === '' || $createdBy === '') {
            $this->setError('asset_type_id, qr_code, asset_name, gender, vendor_id, sector_id, circle_id, latitude, longitude, created_by are required.', 400);
            return $this->response();
        }

        $model = new SanitationAssetsModel();

        if ($model->select('sanitation_asset_id')->where('qr_code', $qrCode)->first()) {
            $this->setError('qr_code already exists.', 409);
            return $this->response();
        }

        if ($shortUrl === '') {
            $shortUrl = generateShortUrl($qrCode);
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
            'photo'  => $photoUrl,
            'created_by'         => $this->_userData['user_id'],
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create sanitation asset.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Sanitation asset tagging created successfully.');
        $this->setOutput($row);
        return $this->response();
    }
}

