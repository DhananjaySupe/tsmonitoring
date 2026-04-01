<?php

namespace App\Controllers;

use App\Models\ShiftsModel;

class Shifts extends BaseController
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
        if (! $this->CheckUserTypePermissions('shift:view')) {
            return $this->response();
        }

        $model = new ShiftsModel();
        $page      = (int) $this->getParam('page', 1);
        $length    = (int) $this->getParam('per_page', 25);
        $keywords  = $this->getParam('keywords', '');
        $isActive  = $this->getParam('is_active', '');
        $orderCol  = $this->getParam('order_by_col', 'shift_id');
        $orderDir  = $this->getParam('order_by', 'DESC');

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

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'shifts' => $rows]);
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
        if (! $this->CheckUserTypePermissions('shift:view')) {
            return $this->response();
        }

        $shiftId = (int) $id;
        if ($shiftId < 1) {
            $this->setError('Invalid shift id.', 400);
            return $this->response();
        }

        $model = new ShiftsModel();
        $row   = $model->find($shiftId);
        if (! $row) {
            $this->setError('Shift not found.', 404);
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
        if (! $this->CheckUserTypePermissions('shift:create')) {
            return $this->response();
        }

        $name     = $this->getPost('shift_name', '');
        $start    = $this->getPost('start_time', '');
        $end      = $this->getPost('end_time', '');
        $isActive = $this->getPost('is_active', 1);

        if ($name === '' || $start === '' || $end === '') {
            $this->setError('shift_name, start_time and end_time are required.', 400);
            return $this->response();
        }

        $model = new ShiftsModel();
        $data = [
            'shift_name' => $name,
            'start_time' => $start,
            'end_time'   => $end,
            'is_active'  => (int) $isActive,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create shift.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Shift created successfully.');
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
        if (! $this->CheckUserTypePermissions('shift:edit')) {
            return $this->response();
        }

        $shiftId = (int) $id;
        if ($shiftId < 1) {
            $this->setError('Invalid shift id.', 400);
            return $this->response();
        }

        $model = new ShiftsModel();
        $row   = $model->find($shiftId);
        if (! $row) {
            $this->setError('Shift not found.', 404);
            return $this->response();
        }

        $name     = $this->getPost('shift_name', $row['shift_name'] ?? '');
        $start    = $this->getPost('start_time', $row['start_time'] ?? '');
        $end      = $this->getPost('end_time', $row['end_time'] ?? '');
        $isActive = $this->getPost('is_active', $row['is_active'] ?? 1);

        if ($name === '' || $start === '' || $end === '') {
            $this->setError('shift_name, start_time and end_time are required.', 400);
            return $this->response();
        }

        $data = [
            'shift_name' => $name,
            'start_time' => $start,
            'end_time'   => $end,
            'is_active'  => (int) $isActive,
        ];

        $model->update($shiftId, $data);
        $updated = $model->find($shiftId);
        $this->setSuccess('Shift updated successfully.');
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
            if (! $this->CheckUserTypePermissions('shift:delete')) {
            return $this->response();
        }

        $shiftId = (int) $id;
        if ($shiftId < 1) {
            $this->setError('Invalid shift id.', 400);
            return $this->response();
        }

        $model = new ShiftsModel();
        $row   = $model->find($shiftId);
        if (! $row) {
            $this->setError('Shift not found.', 404);
            return $this->response();
        }

        $model->delete($shiftId);
        $this->setSuccess('Shift deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}

