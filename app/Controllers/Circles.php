<?php

namespace App\Controllers;

use App\Models\CirclesModel;

class Circles extends BaseController
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
        if (! $this->CheckUserTypePermissions('circle:view')) {
            return $this->response();
        }

        $model = new CirclesModel();
        $page      = (int) $this->getParam('page', 1);
        $length    = (int) $this->getParam('per_page', 25);
        $keywords  = $this->getParam('keywords', '');
        $sectorId  = $this->getParam('sector_id', '');
        $orderCol  = $this->getParam('order_by_col', 'circle_id');
        $orderDir  = $this->getParam('order_by', 'DESC');

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

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'circles' => $rows]);
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
        if (! $this->CheckUserTypePermissions('circle:view')) {
            return $this->response();
        }

        $circleId = (int) $id;
        if ($circleId < 1) {
            $this->setError('Invalid circle id.', 400);
            return $this->response();
        }

        $model = new CirclesModel();
        $row   = $model->find($circleId);
        if (! $row) {
            $this->setError('Circle not found.', 404);
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
        if (! $this->CheckUserTypePermissions('circle:create')) {
            return $this->response();
        }

        $name       = $this->getPost('circle_name', '');
        $code       = $this->getPost('circle_code', '');
        $sectorId   = $this->getPost('sector_id', '');
        $boundary   = $this->getPost('boundary_coordinates', null);

        if ($name === '' || $code === '' || $sectorId === '') {
            $this->setError('circle_name, circle_code and sector_id are required.', 400);
            return $this->response();
        }

        $model = new CirclesModel();
        if ($model->where('circle_code', $code)->first()) {
            $this->setError('circle_code already exists.', 409);
            return $this->response();
        }

        $data = [
            'circle_name'          => $name,
            'circle_code'          => $code,
            'sector_id'            => (int) $sectorId,
            'boundary_coordinates' => $boundary,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create circle.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Circle created successfully.');
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
        if (! $this->CheckUserTypePermissions('circle:edit')) {
            return $this->response();
        }

        $circleId = (int) $id;
        if ($circleId < 1) {
            $this->setError('Invalid circle id.', 400);
            return $this->response();
        }

        $model = new CirclesModel();
        $row   = $model->find($circleId);
        if (! $row) {
            $this->setError('Circle not found.', 404);
            return $this->response();
        }

        $name       = $this->getPost('circle_name', $row['circle_name'] ?? '');
        $code       = $this->getPost('circle_code', $row['circle_code'] ?? '');
        $sectorId   = $this->getPost('sector_id', $row['sector_id'] ?? '');
        $boundary   = $this->getPost('boundary_coordinates', $row['boundary_coordinates'] ?? null);

        if ($name === '' || $code === '' || $sectorId === '') {
            $this->setError('circle_name, circle_code and sector_id are required.', 400);
            return $this->response();
        }

        if ($model->where('circle_code', $code)->where('circle_id !=', $circleId)->first()) {
            $this->setError('circle_code already exists.', 409);
            return $this->response();
        }

        $data = [
            'circle_name'          => $name,
            'circle_code'          => $code,
            'sector_id'            => (int) $sectorId,
            'boundary_coordinates' => $boundary,
        ];

        $model->update($circleId, $data);
        $updated = $model->find($circleId);
        $this->setSuccess('Circle updated successfully.');
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
        if (! $this->CheckUserTypePermissions('circle:delete')) {
            return $this->response();
        }

        $circleId = (int) $id;
        if ($circleId < 1) {
            $this->setError('Invalid circle id.', 400);
            return $this->response();
        }

        $model = new CirclesModel();
        $row   = $model->find($circleId);
        if (! $row) {
            $this->setError('Circle not found.', 404);
            return $this->response();
        }

        $model->delete($circleId);
        $this->setSuccess('Circle deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}

