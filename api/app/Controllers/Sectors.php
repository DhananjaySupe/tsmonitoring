<?php

namespace App\Controllers;

use App\Models\SectorsModel;

class Sectors extends BaseController
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
        if (! $this->CheckUserTypePermissions('sector:view')) {
            return $this->response();
        }

        $model = new SectorsModel();
        $page      = (int) $this->getParam('page', 1);
        $length    = (int) $this->getParam('per_page', 25);
        $keywords  = $this->getParam('keywords', '');
        $orderCol  = $this->getParam('order_by_col', 'sector_id');
        $orderDir  = $this->getParam('order_by', 'DESC');

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

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'sectors' => $rows]);
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
        if (! $this->CheckUserTypePermissions('sector:view')) {
            return $this->response();
        }

        $sectorId = (int) $id;
        if ($sectorId < 1) {
            $this->setError('Invalid sector id.', 400);
            return $this->response();
        }

        $model = new SectorsModel();
        $row   = $model->find($sectorId);
        if (! $row) {
            $this->setError('Sector not found.', 404);
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
        if (! $this->CheckUserTypePermissions('sector:create')) {
            return $this->response();
        }

        $name       = $this->getPost('sector_name', '');
        $code       = $this->getPost('sector_code', '');
        $boundary   = $this->getPost('boundary_coordinates', null);

        if ($name === '' || $code === '') {
            $this->setError('sector_name and sector_code are required.', 400);
            return $this->response();
        }

        $model = new SectorsModel();
        if ($model->where('sector_code', $code)->first()) {
            $this->setError('sector_code already exists.', 409);
            return $this->response();
        }

        $data = [
            'sector_name'          => $name,
            'sector_code'          => $code,
            'boundary_coordinates' => $boundary,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create sector.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Sector created successfully.');
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
        if (! $this->CheckUserTypePermissions('sector:edit')) {
            return $this->response();
        }

        $sectorId = (int) $id;
        if ($sectorId < 1) {
            $this->setError('Invalid sector id.', 400);
            return $this->response();
        }

        $model = new SectorsModel();
        $row   = $model->find($sectorId);
        if (! $row) {
            $this->setError('Sector not found.', 404);
            return $this->response();
        }

        $name       = $this->getPost('sector_name', $row['sector_name'] ?? '');
        $code       = $this->getPost('sector_code', $row['sector_code'] ?? '');
        $boundary   = $this->getPost('boundary_coordinates', $row['boundary_coordinates'] ?? null);

        if ($name === '' || $code === '') {
            $this->setError('sector_name and sector_code are required.', 400);
            return $this->response();
        }

        if ($model->where('sector_code', $code)->where('sector_id !=', $sectorId)->first()) {
            $this->setError('sector_code already exists.', 409);
            return $this->response();
        }

        $data = [
            'sector_name'          => $name,
            'sector_code'          => $code,
            'boundary_coordinates' => $boundary,
        ];

        $model->update($sectorId, $data);
        $updated = $model->find($sectorId);
        $this->setSuccess('Sector updated successfully.');
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
        if (! $this->CheckUserTypePermissions('sector:delete')) {
            return $this->response();
        }

        $sectorId = (int) $id;
        if ($sectorId < 1) {
            $this->setError('Invalid sector id.', 400);
            return $this->response();
        }

        $model = new SectorsModel();
        $row   = $model->find($sectorId);
        if (! $row) {
            $this->setError('Sector not found.', 404);
            return $this->response();
        }

        $model->delete($sectorId);
        $this->setSuccess('Sector deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}

