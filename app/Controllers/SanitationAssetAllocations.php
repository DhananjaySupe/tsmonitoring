<?php

namespace App\Controllers;

use App\Models\SanitationAssetAllocationsModel;

class SanitationAssetAllocations extends BaseController
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
        if (! $this->CheckUserTypePermissions('allocation:view')) {
            return $this->response();
        }

        $model = new SanitationAssetAllocationsModel();
        $page    = (int) $this->getParam('page', 1);
        $length  = (int) $this->getParam('per_page', 25);
        $assetId = $this->getParam('asset_id', '');
        $swachhagrahiId = $this->getParam('swachhagrahi_id', '');
        $shiftId = $this->getParam('shift_id', '');
        $status  = $this->getParam('status', '');
        $dateFrom = $this->getParam('allocation_date_from', '');
        $dateTo   = $this->getParam('allocation_date_to', '');
        $orderCol = $this->getParam('order_by_col', 'allocation_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();
        if ($assetId !== '') {
            $builder->where('asset_id', (int) $assetId);
        }
        if ($swachhagrahiId !== '') {
            $builder->where('swachhagrahi_id', (int) $swachhagrahiId);
        }
        if ($shiftId !== '') {
            $builder->where('shift_id', (int) $shiftId);
        }
        if ($status !== '') {
            $builder->where('status', $status);
        }
        if ($dateFrom !== '') {
            $builder->where('allocation_date >=', $dateFrom);
        }
        if ($dateTo !== '') {
            $builder->where('allocation_date <=', $dateTo);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'sanitation_asset_allocations' => $rows]);
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
        if (! $this->CheckUserTypePermissions('allocation:view')) {
            return $this->response();
        }

        $allocationId = (int) $id;
        if ($allocationId < 1) {
            $this->setError('Invalid allocation id.', 400);
            return $this->response();
        }

        $model = new SanitationAssetAllocationsModel();
        $row   = $model->find($allocationId);
        if (! $row) {
            $this->setError('Sanitation asset allocation not found.', 404);
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
        if (! $this->CheckUserTypePermissions('allocation:create')) {
            return $this->response();
        }

        $assetId         = (int) $this->getPost('asset_id', 0);
        $swachhagrahiId  = (int) $this->getPost('swachhagrahi_id', 0);
        $shiftId         = (int) $this->getPost('shift_id', 0);
        $allocatedBy     = (int) $this->getPost('allocated_by', 0);
        $allocationDate  = $this->getPost('allocation_date', '');
        $status          = $this->getPost('status', 'ACTIVE');

        if ($assetId < 1 || $swachhagrahiId < 1 || $shiftId < 1 || $allocatedBy < 1 || $allocationDate === '') {
            $this->setError('asset_id, swachhagrahi_id, shift_id, allocated_by and allocation_date are required.', 400);
            return $this->response();
        }

        $validStatus = ['ACTIVE', 'COMPLETED', 'CANCELLED'];
        if (! in_array($status, $validStatus, true)) {
            $status = 'ACTIVE';
        }

        $model = new SanitationAssetAllocationsModel();
        $data = [
            'asset_id'         => $assetId,
            'swachhagrahi_id'  => $swachhagrahiId,
            'shift_id'         => $shiftId,
            'allocated_by'     => $allocatedBy,
            'allocation_date'  => $allocationDate,
            'status'           => $status,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create sanitation asset allocation.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Sanitation asset allocation created successfully.');
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
        if (! $this->CheckUserTypePermissions('allocation:edit')) {
            return $this->response();
        }

        $allocationId = (int) $id;
        if ($allocationId < 1) {
            $this->setError('Invalid allocation id.', 400);
            return $this->response();
        }

        $model = new SanitationAssetAllocationsModel();
        $row   = $model->find($allocationId);
        if (! $row) {
            $this->setError('Sanitation asset allocation not found.', 404);
            return $this->response();
        }

        $assetId         = (int) $this->getPost('asset_id', $row['asset_id'] ?? 0);
        $swachhagrahiId  = (int) $this->getPost('swachhagrahi_id', $row['swachhagrahi_id'] ?? 0);
        $shiftId         = (int) $this->getPost('shift_id', $row['shift_id'] ?? 0);
        $allocatedBy     = (int) $this->getPost('allocated_by', $row['allocated_by'] ?? 0);
        $allocationDate  = $this->getPost('allocation_date', $row['allocation_date'] ?? '');
        $status          = $this->getPost('status', $row['status'] ?? 'ACTIVE');

        if ($assetId < 1 || $swachhagrahiId < 1 || $shiftId < 1 || $allocatedBy < 1 || $allocationDate === '') {
            $this->setError('asset_id, swachhagrahi_id, shift_id, allocated_by and allocation_date are required.', 400);
            return $this->response();
        }

        $validStatus = ['ACTIVE', 'COMPLETED', 'CANCELLED'];
        if (! in_array($status, $validStatus, true)) {
            $status = $row['status'] ?? 'ACTIVE';
        }

        $data = [
            'asset_id'         => $assetId,
            'swachhagrahi_id'  => $swachhagrahiId,
            'shift_id'         => $shiftId,
            'allocated_by'     => $allocatedBy,
            'allocation_date'  => $allocationDate,
            'status'           => $status,
        ];

        $model->update($allocationId, $data);
        $updated = $model->find($allocationId);
        $this->setSuccess('Sanitation asset allocation updated successfully.');
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
        if (! $this->CheckUserTypePermissions('allocation:delete')) {
            return $this->response();
        }

        $allocationId = (int) $id;
        if ($allocationId < 1) {
            $this->setError('Invalid allocation id.', 400);
            return $this->response();
        }

        $model = new SanitationAssetAllocationsModel();
        $row   = $model->find($allocationId);
        if (! $row) {
            $this->setError('Sanitation asset allocation not found.', 404);
            return $this->response();
        }

        $model->delete($allocationId);
        $this->setSuccess('Sanitation asset allocation deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }

    public function getallocations($swachhagrahiId)
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
        if (! $this->CheckUserTypePermissions('allocation:view')) {
            return $this->response();
        }

        $swachhagrahiId = (int) $this->member['user_id'];
        if ($swachhagrahiId < 1) {
            $this->setError('Invalid swachhagrahi id.', 400);
            return $this->response();
        }

        $page    = (int) $this->getParam('page', 1);
        $length  = (int) $this->getParam('per_page', 25);
        $options = [
            'page'     => $page,
            'per_page' => $length,
        ];
        $status   = $this->getParam('status', '');
        $dateFrom = $this->getParam('allocation_date_from', '');
        $dateTo   = $this->getParam('allocation_date_to', '');
        if ($status !== '') {
            $options['status'] = $status;
        }
        if ($dateFrom !== '') {
            $options['allocation_date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $options['allocation_date_to'] = $dateTo;
        }

        $model  = new SanitationAssetAllocationsModel();
        $result = $model->getAllocations($swachhagrahiId, $options);

        $this->setSuccess($this->successMessage);
        $this->setOutput([
            'paging'      => $result['paging'],
            'allocations' => $result['allocations'],
        ]);
        return $this->response();
    }

    /**
     * Get single allocation details with asset, asset type, and inspection questions.
     * GET api/sanitation-asset-allocations/details/(:num)
     */
    public function allocationDetails($id)
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
        if (! $this->CheckUserTypePermissions('allocation:view')) {
            return $this->response();
        }

        $allocationId = (int) $id;
        if ($allocationId < 1) {
            $this->setError('Invalid allocation id.', 400);
            return $this->response();
        }

        $model = new SanitationAssetAllocationsModel();
        $details = $model->getAllocationDetails($allocationId);
        if ($details === null) {
            $this->setError('Sanitation asset allocation not found.', 404);
            return $this->response();
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput($details);
        return $this->response();
    }
}
