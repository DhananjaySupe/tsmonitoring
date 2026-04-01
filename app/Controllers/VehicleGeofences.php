<?php

namespace App\Controllers;

use App\Models\VehicleGeofencesModel;

class VehicleGeofences extends BaseController
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

        $model    = new VehicleGeofencesModel();
        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $pointId  = $this->getParam('point_id', '');
        $isActive = $this->getParam('is_active', '');
        $orderCol = $this->getParam('order_by_col', 'geofence_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($keywords !== '') {
            $k = $model->db->escapeLikeString($keywords);
            $builder->groupStart()
                ->like('geofence_id', $k)
                ->orLike('point_id', $k)
                ->groupEnd();
        }
        if ($pointId !== '') {
            $builder->where('point_id', (int) $pointId);
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

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'geofences' => $rows]);
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

        $geofenceId = (int) $id;
        if ($geofenceId < 1) {
            $this->setError('Invalid geofence id.', 400);
            return $this->response();
        }

        $model = new VehicleGeofencesModel();
        $row   = $model->find($geofenceId);
        if (! $row) {
            $this->setError('Geofence not found.', 404);
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

        $pointId      = $this->getPost('point_id', '');
        $radiusMeters = $this->getPost('radius_meters', '');
        $isActive     = $this->getPost('is_active', 1);

        if ($pointId === '' || $radiusMeters === '') {
            $this->setError('point_id and radius_meters are required.', 400);
            return $this->response();
        }

        $data = [
            'point_id'      => (int) $pointId,
            'radius_meters' => (float) $radiusMeters,
            'is_active'     => (int) $isActive,
        ];

        $model = new VehicleGeofencesModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create geofence.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Geofence created successfully.');
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

        $geofenceId = (int) $id;
        if ($geofenceId < 1) {
            $this->setError('Invalid geofence id.', 400);
            return $this->response();
        }

        $model = new VehicleGeofencesModel();
        $row   = $model->find($geofenceId);
        if (! $row) {
            $this->setError('Geofence not found.', 404);
            return $this->response();
        }

        $pointId      = $this->getPost('point_id', $row['point_id'] ?? '');
        $radiusMeters = $this->getPost('radius_meters', $row['radius_meters'] ?? '');
        $isActive     = $this->getPost('is_active', $row['is_active'] ?? 1);

        if ($pointId === '' || $radiusMeters === '') {
            $this->setError('point_id and radius_meters are required.', 400);
            return $this->response();
        }

        $data = [
            'point_id'      => (int) $pointId,
            'radius_meters' => (float) $radiusMeters,
            'is_active'     => (int) $isActive,
        ];

        $model->update($geofenceId, $data);
        $updated = $model->find($geofenceId);
        $this->setSuccess('Geofence updated successfully.');
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

        $geofenceId = (int) $id;
        if ($geofenceId < 1) {
            $this->setError('Invalid geofence id.', 400);
            return $this->response();
        }

        $model = new VehicleGeofencesModel();
        $row   = $model->find($geofenceId);
        if (! $row) {
            $this->setError('Geofence not found.', 404);
            return $this->response();
        }

        $model->delete($geofenceId);
        $this->setSuccess('Geofence deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
