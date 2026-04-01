<?php

namespace App\Controllers;

use App\Models\VehicleMaintenanceLogsModel;

class VehicleMaintenanceLogs extends BaseController
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

        $model       = new VehicleMaintenanceLogsModel();
        $page        = (int) $this->getParam('page', 1);
        $length      = (int) $this->getParam('per_page', 25);
        $vehicleId   = $this->getParam('vehicle_id', '');
        $vendorId    = $this->getParam('vendor_id', '');
        $maintenanceType = $this->getParam('maintenance_type', '');
        $orderCol    = $this->getParam('order_by_col', 'maintenance_id');
        $orderDir    = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($vehicleId !== '') {
            $builder->where('vehicle_id', (int) $vehicleId);
        }
        if ($vendorId !== '') {
            $builder->where('vendor_id', (int) $vendorId);
        }
        if ($maintenanceType !== '') {
            $builder->where('maintenance_type', $maintenanceType);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'maintenance_logs' => $rows]);
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

        $maintenanceId = (int) $id;
        if ($maintenanceId < 1) {
            $this->setError('Invalid maintenance id.', 400);
            return $this->response();
        }

        $model = new VehicleMaintenanceLogsModel();
        $row   = $model->find($maintenanceId);
        if (! $row) {
            $this->setError('Maintenance log not found.', 404);
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

        $vehicleId   = $this->getPost('vehicle_id', '');
        $maintenanceDate = $this->getPost('maintenance_date', '');
        $maintenanceType = $this->getPost('maintenance_type', '');
        $description = $this->getPost('description', '');
        $cost        = $this->getPost('cost', '');
        $nextMaintenanceDate = $this->getPost('next_maintenance_date', '');
        $vendorId    = $this->getPost('vendor_id', '');

        if ($vehicleId === '' || $maintenanceDate === '' || $maintenanceType === '') {
            $this->setError('vehicle_id, maintenance_date and maintenance_type are required.', 400);
            return $this->response();
        }

        $data = [
            'vehicle_id'             => (int) $vehicleId,
            'maintenance_date'       => $maintenanceDate,
            'maintenance_type'       => $maintenanceType,
            'description'            => $description !== '' ? $description : null,
            'cost'                   => $cost !== '' ? (float) $cost : null,
            'next_maintenance_date'  => $nextMaintenanceDate !== '' ? $nextMaintenanceDate : null,
            'vendor_id'              => $vendorId !== '' ? (int) $vendorId : null,
        ];

        $model = new VehicleMaintenanceLogsModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create maintenance log.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Maintenance log created successfully.');
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

        $maintenanceId = (int) $id;
        if ($maintenanceId < 1) {
            $this->setError('Invalid maintenance id.', 400);
            return $this->response();
        }

        $model = new VehicleMaintenanceLogsModel();
        $row   = $model->find($maintenanceId);
        if (! $row) {
            $this->setError('Maintenance log not found.', 404);
            return $this->response();
        }

        $vehicleId   = $this->getPost('vehicle_id', $row['vehicle_id'] ?? '');
        $maintenanceDate = $this->getPost('maintenance_date', $row['maintenance_date'] ?? '');
        $maintenanceType = $this->getPost('maintenance_type', $row['maintenance_type'] ?? '');
        $description = $this->getPost('description', $row['description'] ?? '');
        $cost        = $this->getPost('cost', $row['cost'] ?? '');
        $nextMaintenanceDate = $this->getPost('next_maintenance_date', $row['next_maintenance_date'] ?? '');
        $vendorId    = $this->getPost('vendor_id', $row['vendor_id'] ?? '');

        if ($vehicleId === '' || $maintenanceDate === '' || $maintenanceType === '') {
            $this->setError('vehicle_id, maintenance_date and maintenance_type are required.', 400);
            return $this->response();
        }

        $data = [
            'vehicle_id'             => (int) $vehicleId,
            'maintenance_date'       => $maintenanceDate,
            'maintenance_type'       => $maintenanceType,
            'description'            => $description !== '' ? $description : null,
            'cost'                   => $cost !== '' ? (float) $cost : null,
            'next_maintenance_date'  => $nextMaintenanceDate !== '' ? $nextMaintenanceDate : null,
            'vendor_id'              => $vendorId !== '' ? (int) $vendorId : null,
        ];

        $model->update($maintenanceId, $data);
        $updated = $model->find($maintenanceId);
        $this->setSuccess('Maintenance log updated successfully.');
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

        $maintenanceId = (int) $id;
        if ($maintenanceId < 1) {
            $this->setError('Invalid maintenance id.', 400);
            return $this->response();
        }

        $model = new VehicleMaintenanceLogsModel();
        $row   = $model->find($maintenanceId);
        if (! $row) {
            $this->setError('Maintenance log not found.', 404);
            return $this->response();
        }

        $model->delete($maintenanceId);
        $this->setSuccess('Maintenance log deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
