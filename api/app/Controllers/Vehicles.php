<?php

namespace App\Controllers;

use App\Models\VehiclesModel;

class Vehicles extends BaseController
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

        $model    = new VehiclesModel();
        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $vendorId = $this->getParam('vendor_id', '');
        $status   = $this->getParam('status', '');
        $vehicleType = $this->getParam('vehicle_type', '');
        $orderCol = $this->getParam('order_by_col', 'vehicle_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($keywords !== '') {
            $k = $model->db->escapeLikeString($keywords);
            $builder->groupStart()
                ->like('vehicle_name', $k)
                ->orLike('vehicle_number', $k)
                ->orLike('rc_number', $k)
                ->orLike('imei_number', $k)
                ->orLike('chassis_number', $k)
                ->groupEnd();
        }
        if ($vendorId !== '') {
            $builder->where('vendor_id', (int) $vendorId);
        }
        if ($status !== '') {
            $builder->where('status', $status);
        }
        if ($vehicleType !== '') {
            $builder->where('vehicle_type', $vehicleType);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'vehicles' => $rows]);
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

        $vehicleId = (int) $id;
        if ($vehicleId < 1) {
            $this->setError('Invalid vehicle id.', 400);
            return $this->response();
        }

        $model = new VehiclesModel();
        $row   = $model->find($vehicleId);
        if (! $row) {
            $this->setError('Vehicle not found.', 404);
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

        $vehicleName   = $this->getPost('vehicle_name', '');
        $vehicleType   = $this->getPost('vehicle_type', '');
        $vehicleNumber = $this->getPost('vehicle_number', '');
        $rcNumber      = $this->getPost('rc_number', '');
        $vendorId      = $this->getPost('vendor_id', '');
        $imeiNumber    = $this->getPost('imei_number', '');
        $chassisNumber = $this->getPost('chassis_number', '');
        $gpsDeviceId   = $this->getPost('gps_device_id', '');
        $registrationDate = $this->getPost('registration_date', '');
        $status        = $this->getPost('status', 'Active');

        if ($vehicleName === '' || $vehicleType === '' || $vehicleNumber === '' || $rcNumber === '' || $vendorId === '' || $imeiNumber === '' || $chassisNumber === '') {
            $this->setError('vehicle_name, vehicle_type, vehicle_number, rc_number, vendor_id, imei_number, chassis_number are required.', 400);
            return $this->response();
        }

        $validTypes = ['Compactor', 'Dumper', 'Loader', 'Mini-truck', 'Tipper'];
        if (! in_array($vehicleType, $validTypes, true)) {
            $this->setError('vehicle_type must be one of: Compactor, Dumper, Loader, Mini-truck, Tipper.', 400);
            return $this->response();
        }

        $validStatus = ['Active', 'Inactive', 'Maintenance', 'Retired'];
        if (! in_array($status, $validStatus, true)) {
            $status = 'Active';
        }

        $model = new VehiclesModel();
        if ($model->where('vehicle_number', $vehicleNumber)->first()) {
            $this->setError('vehicle_number already exists.', 409);
            return $this->response();
        }
        if ($model->where('rc_number', $rcNumber)->first()) {
            $this->setError('rc_number already exists.', 409);
            return $this->response();
        }

        $data = [
            'vehicle_name'       => $vehicleName,
            'vehicle_type'       => $vehicleType,
            'vehicle_number'     => $vehicleNumber,
            'rc_number'          => $rcNumber,
            'vendor_id'          => (int) $vendorId,
            'imei_number'        => $imeiNumber,
            'chassis_number'     => $chassisNumber,
            'gps_device_id'      => $gpsDeviceId !== '' ? $gpsDeviceId : null,
            'registration_date'  => $registrationDate !== '' ? $registrationDate : date('Y-m-d H:i:s'),
            'status'             => $status,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create vehicle.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Vehicle created successfully.');
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

        $vehicleId = (int) $id;
        if ($vehicleId < 1) {
            $this->setError('Invalid vehicle id.', 400);
            return $this->response();
        }

        $model = new VehiclesModel();
        $row   = $model->find($vehicleId);
        if (! $row) {
            $this->setError('Vehicle not found.', 404);
            return $this->response();
        }

        $vehicleName   = $this->getPost('vehicle_name', $row['vehicle_name'] ?? '');
        $vehicleType   = $this->getPost('vehicle_type', $row['vehicle_type'] ?? '');
        $vehicleNumber = $this->getPost('vehicle_number', $row['vehicle_number'] ?? '');
        $rcNumber      = $this->getPost('rc_number', $row['rc_number'] ?? '');
        $vendorId      = $this->getPost('vendor_id', $row['vendor_id'] ?? '');
        $imeiNumber    = $this->getPost('imei_number', $row['imei_number'] ?? '');
        $chassisNumber = $this->getPost('chassis_number', $row['chassis_number'] ?? '');
        $gpsDeviceId   = $this->getPost('gps_device_id', $row['gps_device_id'] ?? '');
        $registrationDate = $this->getPost('registration_date', $row['registration_date'] ?? '');
        $status        = $this->getPost('status', $row['status'] ?? 'Active');

        if ($vehicleName === '' || $vehicleType === '' || $vehicleNumber === '' || $rcNumber === '' || $vendorId === '' || $imeiNumber === '' || $chassisNumber === '') {
            $this->setError('vehicle_name, vehicle_type, vehicle_number, rc_number, vendor_id, imei_number, chassis_number are required.', 400);
            return $this->response();
        }

        $validTypes = ['Compactor', 'Dumper', 'Loader', 'Mini-truck', 'Tipper'];
        if (! in_array($vehicleType, $validTypes, true)) {
            $vehicleType = $row['vehicle_type'] ?? 'Compactor';
        }

        $validStatus = ['Active', 'Inactive', 'Maintenance', 'Retired'];
        if (! in_array($status, $validStatus, true)) {
            $status = $row['status'] ?? 'Active';
        }

        if ($model->where('vehicle_number', $vehicleNumber)->where('vehicle_id !=', $vehicleId)->first()) {
            $this->setError('vehicle_number already exists.', 409);
            return $this->response();
        }
        if ($model->where('rc_number', $rcNumber)->where('vehicle_id !=', $vehicleId)->first()) {
            $this->setError('rc_number already exists.', 409);
            return $this->response();
        }

        $data = [
            'vehicle_name'       => $vehicleName,
            'vehicle_type'       => $vehicleType,
            'vehicle_number'     => $vehicleNumber,
            'rc_number'          => $rcNumber,
            'vendor_id'          => (int) $vendorId,
            'imei_number'        => $imeiNumber,
            'chassis_number'     => $chassisNumber,
            'gps_device_id'      => $gpsDeviceId !== '' ? $gpsDeviceId : null,
            'registration_date'  => $registrationDate !== '' ? $registrationDate : ($row['registration_date'] ?? null),
            'status'             => $status,
        ];

        $model->update($vehicleId, $data);
        $updated = $model->find($vehicleId);
        $this->setSuccess('Vehicle updated successfully.');
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

        $vehicleId = (int) $id;
        if ($vehicleId < 1) {
            $this->setError('Invalid vehicle id.', 400);
            return $this->response();
        }

        $model = new VehiclesModel();
        $row   = $model->find($vehicleId);
        if (! $row) {
            $this->setError('Vehicle not found.', 404);
            return $this->response();
        }

        $model->delete($vehicleId);
        $this->setSuccess('Vehicle deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
