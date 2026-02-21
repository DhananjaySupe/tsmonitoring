<?php

namespace App\Controllers;

use App\Models\VehicleDailyTripSummariesModel;

class VehicleDailyTripSummaries extends BaseController
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

        $model       = new VehicleDailyTripSummariesModel();
        $page        = (int) $this->getParam('page', 1);
        $length      = (int) $this->getParam('per_page', 25);
        $vehicleId   = $this->getParam('vehicle_id', '');
        $routeId     = $this->getParam('route_id', '');
        $tripDate    = $this->getParam('trip_date', '');
        $tripStatus  = $this->getParam('trip_status', '');
        $orderCol    = $this->getParam('order_by_col', 'summary_id');
        $orderDir    = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($vehicleId !== '') {
            $builder->where('vehicle_id', (int) $vehicleId);
        }
        if ($routeId !== '') {
            $builder->where('route_id', (int) $routeId);
        }
        if ($tripDate !== '') {
            $builder->where('trip_date', $tripDate);
        }
        if ($tripStatus !== '') {
            $builder->where('trip_status', $tripStatus);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'summaries' => $rows]);
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

        $summaryId = (int) $id;
        if ($summaryId < 1) {
            $this->setError('Invalid summary id.', 400);
            return $this->response();
        }

        $model = new VehicleDailyTripSummariesModel();
        $row   = $model->find($summaryId);
        if (! $row) {
            $this->setError('Trip summary not found.', 404);
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

        $assignmentId = $this->getPost('assignment_id', '');
        $vehicleId    = $this->getPost('vehicle_id', '');
        $routeId      = $this->getPost('route_id', '');
        $tripDate     = $this->getPost('trip_date', '');
        $startTime    = $this->getPost('start_time', '');
        $endTime      = $this->getPost('end_time', '');
        $totalDistance = $this->getPost('total_distance', '');
        $totalPointsAssigned = $this->getPost('total_points_assigned', '');
        $totalPointsVisited  = $this->getPost('total_points_visited', '');
        $totalPointsMissed   = $this->getPost('total_points_missed', '');
        $totalGarbageCollected = $this->getPost('total_garbage_collected', '');
        $avgSpeed     = $this->getPost('avg_speed', '');
        $maxSpeed     = $this->getPost('max_speed', '');
        $idleTime     = $this->getPost('idle_time', '');
        $movingTime   = $this->getPost('moving_time', '');
        $completionPercentage = $this->getPost('completion_percentage', '');
        $tripStatus   = $this->getPost('trip_status', 'COMPLETED');

        if ($vehicleId === '' || $routeId === '' || $tripDate === '') {
            $this->setError('vehicle_id, route_id and trip_date are required.', 400);
            return $this->response();
        }

        $data = [
            'assignment_id'          => $assignmentId !== '' ? (int) $assignmentId : null,
            'vehicle_id'             => (int) $vehicleId,
            'route_id'               => (int) $routeId,
            'trip_date'              => $tripDate,
            'start_time'             => $startTime !== '' ? $startTime : null,
            'end_time'               => $endTime !== '' ? $endTime : null,
            'total_distance'         => $totalDistance !== '' ? (float) $totalDistance : null,
            'total_points_assigned'  => $totalPointsAssigned !== '' ? (int) $totalPointsAssigned : null,
            'total_points_visited'   => $totalPointsVisited !== '' ? (int) $totalPointsVisited : null,
            'total_points_missed'    => $totalPointsMissed !== '' ? (int) $totalPointsMissed : null,
            'total_garbage_collected' => $totalGarbageCollected !== '' ? (float) $totalGarbageCollected : null,
            'avg_speed'              => $avgSpeed !== '' ? (float) $avgSpeed : null,
            'max_speed'              => $maxSpeed !== '' ? (float) $maxSpeed : null,
            'idle_time'              => $idleTime !== '' ? $idleTime : null,
            'moving_time'            => $movingTime !== '' ? $movingTime : null,
            'completion_percentage'  => $completionPercentage !== '' ? (float) $completionPercentage : null,
            'trip_status'            => $tripStatus !== '' ? $tripStatus : 'COMPLETED',
        ];

        $model = new VehicleDailyTripSummariesModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create trip summary.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Trip summary created successfully.');
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

        $summaryId = (int) $id;
        if ($summaryId < 1) {
            $this->setError('Invalid summary id.', 400);
            return $this->response();
        }

        $model = new VehicleDailyTripSummariesModel();
        $row   = $model->find($summaryId);
        if (! $row) {
            $this->setError('Trip summary not found.', 404);
            return $this->response();
        }

        $assignmentId = $this->getPost('assignment_id', $row['assignment_id'] ?? '');
        $vehicleId    = $this->getPost('vehicle_id', $row['vehicle_id'] ?? '');
        $routeId      = $this->getPost('route_id', $row['route_id'] ?? '');
        $tripDate     = $this->getPost('trip_date', $row['trip_date'] ?? '');
        $startTime    = $this->getPost('start_time', $row['start_time'] ?? '');
        $endTime      = $this->getPost('end_time', $row['end_time'] ?? '');
        $totalDistance = $this->getPost('total_distance', $row['total_distance'] ?? '');
        $totalPointsAssigned = $this->getPost('total_points_assigned', $row['total_points_assigned'] ?? '');
        $totalPointsVisited  = $this->getPost('total_points_visited', $row['total_points_visited'] ?? '');
        $totalPointsMissed   = $this->getPost('total_points_missed', $row['total_points_missed'] ?? '');
        $totalGarbageCollected = $this->getPost('total_garbage_collected', $row['total_garbage_collected'] ?? '');
        $avgSpeed     = $this->getPost('avg_speed', $row['avg_speed'] ?? '');
        $maxSpeed     = $this->getPost('max_speed', $row['max_speed'] ?? '');
        $idleTime     = $this->getPost('idle_time', $row['idle_time'] ?? '');
        $movingTime   = $this->getPost('moving_time', $row['moving_time'] ?? '');
        $completionPercentage = $this->getPost('completion_percentage', $row['completion_percentage'] ?? '');
        $tripStatus   = $this->getPost('trip_status', $row['trip_status'] ?? 'COMPLETED');

        if ($vehicleId === '' || $routeId === '' || $tripDate === '') {
            $this->setError('vehicle_id, route_id and trip_date are required.', 400);
            return $this->response();
        }

        $data = [
            'assignment_id'          => $assignmentId !== '' ? (int) $assignmentId : null,
            'vehicle_id'             => (int) $vehicleId,
            'route_id'               => (int) $routeId,
            'trip_date'              => $tripDate,
            'start_time'             => $startTime !== '' ? $startTime : null,
            'end_time'               => $endTime !== '' ? $endTime : null,
            'total_distance'         => $totalDistance !== '' ? (float) $totalDistance : null,
            'total_points_assigned'  => $totalPointsAssigned !== '' ? (int) $totalPointsAssigned : null,
            'total_points_visited'   => $totalPointsVisited !== '' ? (int) $totalPointsVisited : null,
            'total_points_missed'    => $totalPointsMissed !== '' ? (int) $totalPointsMissed : null,
            'total_garbage_collected' => $totalGarbageCollected !== '' ? (float) $totalGarbageCollected : null,
            'avg_speed'              => $avgSpeed !== '' ? (float) $avgSpeed : null,
            'max_speed'              => $maxSpeed !== '' ? (float) $maxSpeed : null,
            'idle_time'              => $idleTime !== '' ? $idleTime : null,
            'moving_time'            => $movingTime !== '' ? $movingTime : null,
            'completion_percentage'  => $completionPercentage !== '' ? (float) $completionPercentage : null,
            'trip_status'            => $tripStatus !== '' ? $tripStatus : 'COMPLETED',
        ];

        $model->update($summaryId, $data);
        $updated = $model->find($summaryId);
        $this->setSuccess('Trip summary updated successfully.');
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

        $summaryId = (int) $id;
        if ($summaryId < 1) {
            $this->setError('Invalid summary id.', 400);
            return $this->response();
        }

        $model = new VehicleDailyTripSummariesModel();
        $row   = $model->find($summaryId);
        if (! $row) {
            $this->setError('Trip summary not found.', 404);
            return $this->response();
        }

        $model->delete($summaryId);
        $this->setSuccess('Trip summary deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
