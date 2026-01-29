<?php

namespace App\Controllers;

use App\Models\AssetTypesModel;

class AssetTypes extends BaseController
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
        if (! $this->CheckUserTypePermissions('asset-type:view')) {
            return $this->response();
        }

        $model    = new AssetTypesModel();
        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $type     = $this->getParam('type', '');
        $status   = $this->getParam('status', '');
        $orderCol = $this->getParam('order_by_col', 'asset_type_id');
        $orderDir = $this->getParam('order_by', 'DESC');

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

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'asset_types' => $rows]);
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
        if (! $this->CheckUserTypePermissions('asset-type:view')) {
            return $this->response();
        }

        $assetTypeId = (int) $id;
        if ($assetTypeId < 1) {
            $this->setError('Invalid asset_type id.', 400);
            return $this->response();
        }

        $model = new AssetTypesModel();
        $row   = $model->find($assetTypeId);
        if (! $row) {
            $this->setError('Asset type not found.', 404);
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
        if (! $this->CheckUserTypePermissions('asset-type:create')) {
            return $this->response();
        }

        $type        = $this->getPost('type', 'SANITATION');
        $name        = $this->getPost('name', '');
        $description = $this->getPost('description', '');
        $questions   = $this->getPost('questions', '');
        $status      = (int) $this->getPost('status', 1);

        if ($name === '' || $description === '' || $questions === '') {
            $this->setError('name, description and questions are required.', 400);
            return $this->response();
        }

        $model = new AssetTypesModel();
        if ($model->where('name', $name)->first()) {
            $this->setError('Asset type name already exists.', 409);
            return $this->response();
        }

        $data = [
            'type'        => $type,
            'name'        => $name,
            'description' => $description,
            'questions'   => $questions,
            'status'      => $status,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create asset type.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Asset type created successfully.');
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
        if (! $this->CheckUserTypePermissions('asset-type:edit')) {
            return $this->response();
        }

        $assetTypeId = (int) $id;
        if ($assetTypeId < 1) {
            $this->setError('Invalid asset_type id.', 400);
            return $this->response();
        }

        $model = new AssetTypesModel();
        $row   = $model->find($assetTypeId);
        if (! $row) {
            $this->setError('Asset type not found.', 404);
            return $this->response();
        }

        $type        = $this->getPost('type', $row['type'] ?? 'SANITATION');
        $name        = $this->getPost('name', $row['name'] ?? '');
        $description = $this->getPost('description', $row['description'] ?? '');
        $questions   = $this->getPost('questions', $row['questions'] ?? '');
        $status      = (int) $this->getPost('status', $row['status'] ?? 1);

        if ($name === '' || $description === '' || $questions === '') {
            $this->setError('name, description and questions are required.', 400);
            return $this->response();
        }

        if ($model->where('name', $name)->where('asset_type_id !=', $assetTypeId)->first()) {
            $this->setError('Asset type name already exists.', 409);
            return $this->response();
        }

        $data = [
            'type'        => $type,
            'name'        => $name,
            'description' => $description,
            'questions'   => $questions,
            'status'      => $status,
        ];

        $model->update($assetTypeId, $data);
        $updated = $model->find($assetTypeId);
        $this->setSuccess('Asset type updated successfully.');
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
        if (! $this->CheckUserTypePermissions('asset-type:delete')) {
            return $this->response();
        }

        $assetTypeId = (int) $id;
        if ($assetTypeId < 1) {
            $this->setError('Invalid asset_type id.', 400);
            return $this->response();
        }

        $model = new AssetTypesModel();
        $row   = $model->find($assetTypeId);
        if (! $row) {
            $this->setError('Asset type not found.', 404);
            return $this->response();
        }

        $model->delete($assetTypeId);
        $this->setSuccess('Asset type deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}

