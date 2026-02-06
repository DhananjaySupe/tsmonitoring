<?php

namespace App\Controllers;

use App\Models\VehiclePerformanceMetricsModel;

class VehiclePerformanceMetrics extends BaseController
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

        $model     = new VehiclePerformanceMetricsModel();
        $page      = (int) $this->getParam('page', 1);
        $length    = (int) $this->getParam('per_page', 25);
        $vehicleId = $this->getParam('vehicle_id', '');
        $routeId   = $this->getParam('route_id', '');
        $metricType = $this->getParam('metric_type', '');
        $metricDate = $this->getParam('metric_date', '');
        $orderCol  = $this->getParam('order_by_col', 'metric_id');
        $orderDir  = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($vehicleId !== '') {
            $builder->where('vehicle_id', (int) $vehicleId);
        }
        if ($routeId !== '') {
            $builder->where('route_id', (int) $routeId);
        }
        if ($metricType !== '') {
            $builder->where('metric_type', $metricType);
        }
        if ($metricDate !== '') {
            $builder->where('metric_date', $metricDate);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'metrics' => $rows]);
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

        $metricId = (int) $id;
        if ($metricId < 1) {
            $this->setError('Invalid metric id.', 400);
            return $this->response();
        }

        $model = new VehiclePerformanceMetricsModel();
        $row   = $model->find($metricId);
        if (! $row) {
            $this->setError('Performance metric not found.', 404);
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
        $routeId     = $this->getPost('route_id', '');
        $metricDate  = $this->getPost('metric_date', '');
        $metricType  = $this->getPost('metric_type', '');
        $metricValue = $this->getPost('metric_value', '');

        if ($vehicleId === '' || $metricDate === '' || $metricType === '' || $metricValue === '') {
            $this->setError('vehicle_id, metric_date, metric_type and metric_value are required.', 400);
            return $this->response();
        }

        $data = [
            'vehicle_id'   => (int) $vehicleId,
            'route_id'     => $routeId !== '' ? (int) $routeId : null,
            'metric_date'   => $metricDate,
            'metric_type'  => $metricType,
            'metric_value' => $metricValue,
        ];

        $model = new VehiclePerformanceMetricsModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create performance metric.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Performance metric created successfully.');
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

        $metricId = (int) $id;
        if ($metricId < 1) {
            $this->setError('Invalid metric id.', 400);
            return $this->response();
        }

        $model = new VehiclePerformanceMetricsModel();
        $row   = $model->find($metricId);
        if (! $row) {
            $this->setError('Performance metric not found.', 404);
            return $this->response();
        }

        $vehicleId   = $this->getPost('vehicle_id', $row['vehicle_id'] ?? '');
        $routeId     = $this->getPost('route_id', $row['route_id'] ?? '');
        $metricDate  = $this->getPost('metric_date', $row['metric_date'] ?? '');
        $metricType  = $this->getPost('metric_type', $row['metric_type'] ?? '');
        $metricValue = $this->getPost('metric_value', $row['metric_value'] ?? '');

        if ($vehicleId === '' || $metricDate === '' || $metricType === '' || $metricValue === '') {
            $this->setError('vehicle_id, metric_date, metric_type and metric_value are required.', 400);
            return $this->response();
        }

        $data = [
            'vehicle_id'   => (int) $vehicleId,
            'route_id'     => $routeId !== '' ? (int) $routeId : null,
            'metric_date'   => $metricDate,
            'metric_type'  => $metricType,
            'metric_value' => $metricValue,
        ];

        $model->update($metricId, $data);
        $updated = $model->find($metricId);
        $this->setSuccess('Performance metric updated successfully.');
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

        $metricId = (int) $id;
        if ($metricId < 1) {
            $this->setError('Invalid metric id.', 400);
            return $this->response();
        }

        $model = new VehiclePerformanceMetricsModel();
        $row   = $model->find($metricId);
        if (! $row) {
            $this->setError('Performance metric not found.', 404);
            return $this->response();
        }

        $model->delete($metricId);
        $this->setSuccess('Performance metric deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
