<?php

namespace App\Controllers;

use App\Models\VehicleRoutePointsModel;

class VehicleRoutePoints extends BaseController
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

        $model     = new VehicleRoutePointsModel();
        $page      = (int) $this->getParam('page', 1);
        $length    = (int) $this->getParam('per_page', 25);
        $routeId   = $this->getParam('route_id', '');
        $pointId   = $this->getParam('point_id', '');
        $orderCol  = $this->getParam('order_by_col', 'route_point_id');
        $orderDir  = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($routeId !== '') {
            $builder->where('route_id', (int) $routeId);
        }
        if ($pointId !== '') {
            $builder->where('point_id', (int) $pointId);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'route_points' => $rows]);
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

        $routePointId = (int) $id;
        if ($routePointId < 1) {
            $this->setError('Invalid route point id.', 400);
            return $this->response();
        }

        $model = new VehicleRoutePointsModel();
        $row   = $model->find($routePointId);
        if (! $row) {
            $this->setError('Route point not found.', 404);
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

        $routeId   = $this->getPost('route_id', '');
        $pointId   = $this->getPost('point_id', '');
        $sequenceNumber = $this->getPost('sequence_number', '');
        $estimatedArrivalTime = $this->getPost('estimated_arrival_time', '');
        $expectedStayDuration = $this->getPost('expected_stay_duration', '');

        if ($routeId === '' || $pointId === '' || $sequenceNumber === '') {
            $this->setError('route_id, point_id and sequence_number are required.', 400);
            return $this->response();
        }

        $data = [
            'route_id'                => (int) $routeId,
            'point_id'                => (int) $pointId,
            'sequence_number'          => (int) $sequenceNumber,
            'estimated_arrival_time'   => $estimatedArrivalTime !== '' ? $estimatedArrivalTime : null,
            'expected_stay_duration'  => $expectedStayDuration !== '' ? $expectedStayDuration : null,
        ];

        $model = new VehicleRoutePointsModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create route point.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Route point created successfully.');
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

        $routePointId = (int) $id;
        if ($routePointId < 1) {
            $this->setError('Invalid route point id.', 400);
            return $this->response();
        }

        $model = new VehicleRoutePointsModel();
        $row   = $model->find($routePointId);
        if (! $row) {
            $this->setError('Route point not found.', 404);
            return $this->response();
        }

        $routeId   = $this->getPost('route_id', $row['route_id'] ?? '');
        $pointId   = $this->getPost('point_id', $row['point_id'] ?? '');
        $sequenceNumber = $this->getPost('sequence_number', $row['sequence_number'] ?? '');
        $estimatedArrivalTime = $this->getPost('estimated_arrival_time', $row['estimated_arrival_time'] ?? '');
        $expectedStayDuration = $this->getPost('expected_stay_duration', $row['expected_stay_duration'] ?? '');

        if ($routeId === '' || $pointId === '' || $sequenceNumber === '') {
            $this->setError('route_id, point_id and sequence_number are required.', 400);
            return $this->response();
        }

        $data = [
            'route_id'                => (int) $routeId,
            'point_id'                => (int) $pointId,
            'sequence_number'          => (int) $sequenceNumber,
            'estimated_arrival_time'   => $estimatedArrivalTime !== '' ? $estimatedArrivalTime : null,
            'expected_stay_duration'  => $expectedStayDuration !== '' ? $expectedStayDuration : null,
        ];

        $model->update($routePointId, $data);
        $updated = $model->find($routePointId);
        $this->setSuccess('Route point updated successfully.');
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

        $routePointId = (int) $id;
        if ($routePointId < 1) {
            $this->setError('Invalid route point id.', 400);
            return $this->response();
        }

        $model = new VehicleRoutePointsModel();
        $row   = $model->find($routePointId);
        if (! $row) {
            $this->setError('Route point not found.', 404);
            return $this->response();
        }

        $model->delete($routePointId);
        $this->setSuccess('Route point deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
