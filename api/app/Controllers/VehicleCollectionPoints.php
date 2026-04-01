<?php

namespace App\Controllers;

use App\Models\VehicleCollectionPointsModel;

class VehicleCollectionPoints extends BaseController
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

        $model    = new VehicleCollectionPointsModel();
        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $status   = $this->getParam('status', '');
        $orderCol = $this->getParam('order_by_col', 'point_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($keywords !== '') {
            $k = $model->db->escapeLikeString($keywords);
            $builder->groupStart()
                ->like('point_code', $k)
                ->orLike('point_name', $k)
                ->orLike('address', $k)
                ->orLike('ward_number', $k)
                ->orLike('zone', $k)
                ->groupEnd();
        }
        if ($status !== '') {
            $builder->where('status', $status);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'collection_points' => $rows]);
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

        $pointId = (int) $id;
        if ($pointId < 1) {
            $this->setError('Invalid point id.', 400);
            return $this->response();
        }

        $model = new VehicleCollectionPointsModel();
        $row   = $model->find($pointId);
        if (! $row) {
            $this->setError('Collection point not found.', 404);
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

        $pointCode   = $this->getPost('point_code', '');
        $pointName   = $this->getPost('point_name', '');
        $latitude    = $this->getPost('latitude', '');
        $longitude   = $this->getPost('longitude', '');
        $address     = $this->getPost('address', '');
        $wardNumber  = $this->getPost('ward_number', '');
        $zone        = $this->getPost('zone', '');
        $pointType   = $this->getPost('point_type', '');
        $expectedCollectionTime = $this->getPost('expected_collection_time', '');
        $collectionFrequency    = $this->getPost('collection_frequency', '');
        $status      = $this->getPost('status', 'ACTIVE');

        if ($pointCode === '' || $pointName === '') {
            $this->setError('point_code and point_name are required.', 400);
            return $this->response();
        }

        $data = [
            'point_code'               => $pointCode,
            'point_name'               => $pointName,
            'latitude'                 => $latitude !== '' ? $latitude : null,
            'longitude'                => $longitude !== '' ? $longitude : null,
            'address'                  => $address !== '' ? $address : null,
            'ward_number'              => $wardNumber !== '' ? $wardNumber : null,
            'zone'                     => $zone !== '' ? $zone : null,
            'point_type'               => $pointType !== '' ? $pointType : null,
            'expected_collection_time' => $expectedCollectionTime !== '' ? $expectedCollectionTime : null,
            'collection_frequency'     => $collectionFrequency !== '' ? $collectionFrequency : null,
            'status'                   => $status !== '' ? $status : 'ACTIVE',
        ];

        $model = new VehicleCollectionPointsModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create collection point.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Collection point created successfully.');
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

        $pointId = (int) $id;
        if ($pointId < 1) {
            $this->setError('Invalid point id.', 400);
            return $this->response();
        }

        $model = new VehicleCollectionPointsModel();
        $row   = $model->find($pointId);
        if (! $row) {
            $this->setError('Collection point not found.', 404);
            return $this->response();
        }

        $pointCode   = $this->getPost('point_code', $row['point_code'] ?? '');
        $pointName   = $this->getPost('point_name', $row['point_name'] ?? '');
        $latitude    = $this->getPost('latitude', $row['latitude'] ?? '');
        $longitude   = $this->getPost('longitude', $row['longitude'] ?? '');
        $address     = $this->getPost('address', $row['address'] ?? '');
        $wardNumber  = $this->getPost('ward_number', $row['ward_number'] ?? '');
        $zone        = $this->getPost('zone', $row['zone'] ?? '');
        $pointType   = $this->getPost('point_type', $row['point_type'] ?? '');
        $expectedCollectionTime = $this->getPost('expected_collection_time', $row['expected_collection_time'] ?? '');
        $collectionFrequency    = $this->getPost('collection_frequency', $row['collection_frequency'] ?? '');
        $status      = $this->getPost('status', $row['status'] ?? 'ACTIVE');

        if ($pointCode === '' || $pointName === '') {
            $this->setError('point_code and point_name are required.', 400);
            return $this->response();
        }

        $data = [
            'point_code'               => $pointCode,
            'point_name'               => $pointName,
            'latitude'                 => $latitude !== '' ? $latitude : null,
            'longitude'                => $longitude !== '' ? $longitude : null,
            'address'                  => $address !== '' ? $address : null,
            'ward_number'              => $wardNumber !== '' ? $wardNumber : null,
            'zone'                     => $zone !== '' ? $zone : null,
            'point_type'               => $pointType !== '' ? $pointType : null,
            'expected_collection_time' => $expectedCollectionTime !== '' ? $expectedCollectionTime : null,
            'collection_frequency'     => $collectionFrequency !== '' ? $collectionFrequency : null,
            'status'                   => $status !== '' ? $status : 'ACTIVE',
        ];

        $model->update($pointId, $data);
        $updated = $model->find($pointId);
        $this->setSuccess('Collection point updated successfully.');
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

        $pointId = (int) $id;
        if ($pointId < 1) {
            $this->setError('Invalid point id.', 400);
            return $this->response();
        }

        $model = new VehicleCollectionPointsModel();
        $row   = $model->find($pointId);
        if (! $row) {
            $this->setError('Collection point not found.', 404);
            return $this->response();
        }

        $model->delete($pointId);
        $this->setSuccess('Collection point deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
