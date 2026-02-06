<?php

namespace App\Controllers;

use App\Models\VehicleGpsTrackingModel;

class VehicleGpsTracking extends BaseController
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

        $model       = new VehicleGpsTrackingModel();
        $page        = (int) $this->getParam('page', 1);
        $length      = (int) $this->getParam('per_page', 25);
        $vehicleId   = $this->getParam('vehicle_id', '');
        $assignmentId = $this->getParam('assignment_id', '');
        $orderCol    = $this->getParam('order_by_col', 'tracking_id');
        $orderDir    = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($vehicleId !== '') {
            $builder->where('vehicle_id', (int) $vehicleId);
        }
        if ($assignmentId !== '') {
            $builder->where('assignment_id', (int) $assignmentId);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'tracking' => $rows]);
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

        $trackingId = (int) $id;
        if ($trackingId < 1) {
            $this->setError('Invalid tracking id.', 400);
            return $this->response();
        }

        $model = new VehicleGpsTrackingModel();
        $row   = $model->find($trackingId);
        if (! $row) {
            $this->setError('GPS tracking record not found.', 404);
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

        $vehicleId     = $this->getPost('vehicle_id', '');
        $assignmentId  = $this->getPost('assignment_id', '');
        $latitude      = $this->getPost('latitude', '');
        $longitude     = $this->getPost('longitude', '');
        $speed         = $this->getPost('speed', '');
        $direction     = $this->getPost('direction', '');
        $ignitionStatus = $this->getPost('ignition_status', '');
        $fuelLevel     = $this->getPost('fuel_level', '');
        $odometerReading = $this->getPost('odometer_reading', '');
        $accuracy      = $this->getPost('accuracy', '');
        $timestamp     = $this->getPost('timestamp', date('Y-m-d H:i:s'));

        if ($vehicleId === '' || $latitude === '' || $longitude === '') {
            $this->setError('vehicle_id, latitude and longitude are required.', 400);
            return $this->response();
        }

        $data = [
            'vehicle_id'       => (int) $vehicleId,
            'assignment_id'    => $assignmentId !== '' ? (int) $assignmentId : null,
            'latitude'         => (float) $latitude,
            'longitude'        => (float) $longitude,
            'speed'            => $speed !== '' ? (float) $speed : null,
            'direction'        => $direction !== '' ? (float) $direction : null,
            'ignition_status'  => $ignitionStatus !== '' ? $ignitionStatus : null,
            'fuel_level'       => $fuelLevel !== '' ? (float) $fuelLevel : null,
            'odometer_reading' => $odometerReading !== '' ? (float) $odometerReading : null,
            'accuracy'         => $accuracy !== '' ? (float) $accuracy : null,
            'timestamp'        => $timestamp !== '' ? $timestamp : date('Y-m-d H:i:s'),
        ];

        $model = new VehicleGpsTrackingModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create GPS tracking record.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('GPS tracking record created successfully.');
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

        $trackingId = (int) $id;
        if ($trackingId < 1) {
            $this->setError('Invalid tracking id.', 400);
            return $this->response();
        }

        $model = new VehicleGpsTrackingModel();
        $row   = $model->find($trackingId);
        if (! $row) {
            $this->setError('GPS tracking record not found.', 404);
            return $this->response();
        }

        $vehicleId     = $this->getPost('vehicle_id', $row['vehicle_id'] ?? '');
        $assignmentId  = $this->getPost('assignment_id', $row['assignment_id'] ?? '');
        $latitude      = $this->getPost('latitude', $row['latitude'] ?? '');
        $longitude     = $this->getPost('longitude', $row['longitude'] ?? '');
        $speed         = $this->getPost('speed', $row['speed'] ?? '');
        $direction     = $this->getPost('direction', $row['direction'] ?? '');
        $ignitionStatus = $this->getPost('ignition_status', $row['ignition_status'] ?? '');
        $fuelLevel     = $this->getPost('fuel_level', $row['fuel_level'] ?? '');
        $odometerReading = $this->getPost('odometer_reading', $row['odometer_reading'] ?? '');
        $accuracy      = $this->getPost('accuracy', $row['accuracy'] ?? '');
        $timestamp     = $this->getPost('timestamp', $row['timestamp'] ?? '');

        if ($vehicleId === '' || $latitude === '' || $longitude === '') {
            $this->setError('vehicle_id, latitude and longitude are required.', 400);
            return $this->response();
        }

        $data = [
            'vehicle_id'       => (int) $vehicleId,
            'assignment_id'    => $assignmentId !== '' ? (int) $assignmentId : null,
            'latitude'         => (float) $latitude,
            'longitude'        => (float) $longitude,
            'speed'            => $speed !== '' ? (float) $speed : null,
            'direction'        => $direction !== '' ? (float) $direction : null,
            'ignition_status'  => $ignitionStatus !== '' ? $ignitionStatus : null,
            'fuel_level'       => $fuelLevel !== '' ? (float) $fuelLevel : null,
            'odometer_reading' => $odometerReading !== '' ? (float) $odometerReading : null,
            'accuracy'         => $accuracy !== '' ? (float) $accuracy : null,
            'timestamp'        => $timestamp !== '' ? $timestamp : ($row['timestamp'] ?? null),
        ];

        $model->update($trackingId, $data);
        $updated = $model->find($trackingId);
        $this->setSuccess('GPS tracking record updated successfully.');
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

        $trackingId = (int) $id;
        if ($trackingId < 1) {
            $this->setError('Invalid tracking id.', 400);
            return $this->response();
        }

        $model = new VehicleGpsTrackingModel();
        $row   = $model->find($trackingId);
        if (! $row) {
            $this->setError('GPS tracking record not found.', 404);
            return $this->response();
        }

        $model->delete($trackingId);
        $this->setSuccess('GPS tracking record deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
