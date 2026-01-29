<?php

namespace App\Controllers;

use App\Models\VendorsModel;

class Vendors extends BaseController
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
        if (! $this->CheckUserTypePermissions('vendor:view')) {
            return $this->response();
        }

        $model = new VendorsModel();
        $page      = (int) $this->getParam('page', 1);
        $length    = (int) $this->getParam('per_page', 25);
        $keywords  = $this->getParam('keywords', '');
        $status    = $this->getParam('status', '');
        $orderCol  = $this->getParam('order_by_col', 'vendor_id');
        $orderDir  = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();
        if ($keywords !== '') {
            $k = $model->db->escapeLikeString($keywords);
            $builder->groupStart()
                ->like('vendor_name', $k)
                ->orLike('vendor_code', $k)
                ->orLike('contact_person', $k)
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
        $this->setOutput(['paging' => $paging, 'vendors' => $rows]);
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
        if (! $this->CheckUserTypePermissions('vendor:view')) {
            return $this->response();
        }

        $vendorId = (int) $id;
        if ($vendorId < 1) {
            $this->setError('Invalid vendor id.', 400);
            return $this->response();
        }

        $model = new VendorsModel();
        $row   = $model->find($vendorId);
        if (! $row) {
            $this->setError('Vendor not found.', 404);
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
        if (! $this->CheckUserTypePermissions('vendor:create')) {
            return $this->response();
        }

        $vendorName   = $this->getPost('vendor_name', '');
        $vendorCode   = $this->getPost('vendor_code', '');
        $contactPerson = $this->getPost('contact_person', '');
        $contactEmail  = $this->getPost('contact_email', '');
        $contactPhone  = $this->getPost('contact_phone', '');
        $address       = $this->getPost('address', '');
        $status        = $this->getPost('status', 'ACTIVE');
        $userId        = $this->getPost('user_id', 0);

        if ($vendorName === '' || $vendorCode === '') {
            $this->setError('vendor_name and vendor_code are required.', 400);
            return $this->response();
        }

        $validStatus = ['ACTIVE', 'INACTIVE', 'SUSPENDED'];
        if (! in_array($status, $validStatus, true)) {
            $status = 'ACTIVE';
        }

        $model = new VendorsModel();
        $existing = $model->where('vendor_code', $vendorCode)->first();
        if ($existing) {
            $this->setError('Vendor code already exists.', 400);
            return $this->response();
        }

        $data = [
            'user_id'        => (int) $userId,
            'vendor_name'    => $vendorName,
            'vendor_code'    => $vendorCode,
            'contact_person' => $contactPerson,
            'contact_email'  => $contactEmail,
            'contact_phone'  => $contactPhone,
            'address'        => $address,
            'status'         => $status,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create vendor.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Vendor created successfully.');
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
        if (! $this->CheckUserTypePermissions('vendor:edit')) {
            return $this->response();
        }

        $vendorId = (int) $id;
        if ($vendorId < 1) {
            $this->setError('Invalid vendor id.', 400);
            return $this->response();
        }

        $model = new VendorsModel();
        $row   = $model->find($vendorId);
        if (! $row) {
            $this->setError('Vendor not found.', 404);
            return $this->response();
        }

        $vendorName   = $this->getPost('vendor_name', $row['vendor_name'] ?? '');
        $vendorCode   = $this->getPost('vendor_code', $row['vendor_code'] ?? '');
        $contactPerson = $this->getPost('contact_person', $row['contact_person'] ?? '');
        $contactEmail  = $this->getPost('contact_email', $row['contact_email'] ?? '');
        $contactPhone  = $this->getPost('contact_phone', $row['contact_phone'] ?? '');
        $address       = $this->getPost('address', $row['address'] ?? '');
        $status        = $this->getPost('status', $row['status'] ?? 'ACTIVE');
        $userId        = $this->getPost('user_id', $row['user_id'] ?? 0);

        if ($vendorName === '' || $vendorCode === '') {
            $this->setError('vendor_name and vendor_code are required.', 400);
            return $this->response();
        }

        $validStatus = ['ACTIVE', 'INACTIVE', 'SUSPENDED'];
        if (! in_array($status, $validStatus, true)) {
            $status = $row['status'] ?? 'ACTIVE';
        }

        $existing = $model->where('vendor_code', $vendorCode)->where('vendor_id !=', $vendorId)->first();
        if ($existing) {
            $this->setError('Vendor code already exists.', 400);
            return $this->response();
        }

        $data = [
            'user_id'        => (int) $userId,
            'vendor_name'    => $vendorName,
            'vendor_code'    => $vendorCode,
            'contact_person' => $contactPerson,
            'contact_email'  => $contactEmail,
            'contact_phone'  => $contactPhone,
            'address'        => $address,
            'status'         => $status,
        ];

        $model->update($vendorId, $data);
        $updated = $model->find($vendorId);
        $this->setSuccess('Vendor updated successfully.');
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
        if (! $this->CheckUserTypePermissions('vendor:delete')) {
            return $this->response();
        }

        $vendorId = (int) $id;
        if ($vendorId < 1) {
            $this->setError('Invalid vendor id.', 400);
            return $this->response();
        }

        $model = new VendorsModel();
        $row   = $model->find($vendorId);
        if (! $row) {
            $this->setError('Vendor not found.', 404);
            return $this->response();
        }

        $model->delete($vendorId);
        $this->setSuccess('Vendor deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
