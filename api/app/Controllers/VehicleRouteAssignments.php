<?php

namespace App\Controllers;

use App\Models\VehicleRouteAssignmentsModel;

class VehicleRouteAssignments extends BaseController
{
    public function index()
    {
        if (! $this->isGet()) { $this->setError($this->methodNotAllowed, 405); return $this->response(); }
        if (! $this->AuthenticateApikey()) { $this->setError($this->invalidApiKey, 401); return $this->response(); }
        if (! $this->AuthenticateToken()) { $this->setError($this->invalidToken, 401); return $this->response(); }

        $model = new VehicleRouteAssignmentsModel();
        $page = (int) $this->getParam('page', 1);
        $length = (int) $this->getParam('per_page', 25);
        $vehicleId = $this->getParam('vehicle_id', '');
        $routeId = $this->getParam('route_id', '');
        $assignmentDate = $this->getParam('assignment_date', '');
        $assignmentStatus = $this->getParam('assignment_status', '');
        $orderCol = $this->getParam('order_by_col', 'assignment_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();
        if ($vehicleId !== '') { $builder->where('vehicle_id', (int) $vehicleId); }
        if ($routeId !== '') { $builder->where('route_id', (int) $routeId); }
        if ($assignmentDate !== '') { $builder->where('assignment_date', $assignmentDate); }
        if ($assignmentStatus !== '') { $builder->where('assignment_status', $assignmentStatus); }

        $totalRecords = $builder->countAllResults(false);
        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);
        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'assignments' => $rows]);
        return $this->response();
    }

    public function view($id)
    {
        if (! $this->isGet()) { $this->setError($this->methodNotAllowed, 405); return $this->response(); }
        if (! $this->AuthenticateApikey()) { $this->setError($this->invalidApiKey, 401); return $this->response(); }
        if (! $this->AuthenticateToken()) { $this->setError($this->invalidToken, 401); return $this->response(); }

        $assignmentId = (int) $id;
        if ($assignmentId < 1) { $this->setError('Invalid assignment id.', 400); return $this->response(); }

        $model = new VehicleRouteAssignmentsModel();
        $row = $model->find($assignmentId);
        if (! $row) { $this->setError('Route assignment not found.', 404); return $this->response(); }

        $this->setSuccess($this->successMessage);
        $this->setOutput($row);
        return $this->response();
    }

    public function create()
    {
        if (! $this->isPost()) { $this->setError($this->methodNotAllowed, 405); return $this->response(); }
        if (! $this->AuthenticateApikey()) { $this->setError($this->invalidApiKey, 401); return $this->response(); }
        if (! $this->AuthenticateToken()) { $this->setError($this->invalidToken, 401); return $this->response(); }

        $vehicleId = $this->getPost('vehicle_id', '');
        $routeId = $this->getPost('route_id', '');
        $assignmentDate = $this->getPost('assignment_date', '');
        $driverId = $this->getPost('driver_id', '');
        $shift = $this->getPost('shift', '');
        $plannedStartTime = $this->getPost('planned_start_time', '');
        $plannedEndTime = $this->getPost('planned_end_time', '');
        $assignmentStatus = $this->getPost('assignment_status', 'SCHEDULED');

        if ($vehicleId === '' || $routeId === '' || $assignmentDate === '') {
            $this->setError('vehicle_id, route_id and assignment_date are required.', 400);
            return $this->response();
        }

        $data = [
            'vehicle_id' => (int) $vehicleId,
            'route_id' => (int) $routeId,
            'assignment_date' => $assignmentDate,
            'driver_id' => $driverId !== '' ? (int) $driverId : null,
            'shift' => $shift !== '' ? $shift : null,
            'planned_start_time' => $plannedStartTime !== '' ? $plannedStartTime : null,
            'planned_end_time' => $plannedEndTime !== '' ? $plannedEndTime : null,
            'assignment_status' => $assignmentStatus !== '' ? $assignmentStatus : 'SCHEDULED',
        ];

        $model = new VehicleRouteAssignmentsModel();
        $id = $model->insert($data, true);
        if (! $id) { $this->setError('Failed to create route assignment.', 500); return $this->response(); }

        $row = $model->find($id);
        $this->setSuccess('Route assignment created successfully.');
        $this->setOutput($row);
        return $this->response();
    }

    public function edit($id)
    {
        if (! $this->isPost()) { $this->setError($this->methodNotAllowed, 405); return $this->response(); }
        if (! $this->AuthenticateApikey()) { $this->setError($this->invalidApiKey, 401); return $this->response(); }
        if (! $this->AuthenticateToken()) { $this->setError($this->invalidToken, 401); return $this->response(); }

        $assignmentId = (int) $id;
        if ($assignmentId < 1) { $this->setError('Invalid assignment id.', 400); return $this->response(); }

        $model = new VehicleRouteAssignmentsModel();
        $row = $model->find($assignmentId);
        if (! $row) { $this->setError('Route assignment not found.', 404); return $this->response(); }

        $vehicleId = $this->getPost('vehicle_id', $row['vehicle_id'] ?? '');
        $routeId = $this->getPost('route_id', $row['route_id'] ?? '');
        $assignmentDate = $this->getPost('assignment_date', $row['assignment_date'] ?? '');
        $driverId = $this->getPost('driver_id', $row['driver_id'] ?? '');
        $shift = $this->getPost('shift', $row['shift'] ?? '');
        $plannedStartTime = $this->getPost('planned_start_time', $row['planned_start_time'] ?? '');
        $plannedEndTime = $this->getPost('planned_end_time', $row['planned_end_time'] ?? '');
        $assignmentStatus = $this->getPost('assignment_status', $row['assignment_status'] ?? 'SCHEDULED');

        if ($vehicleId === '' || $routeId === '' || $assignmentDate === '') {
            $this->setError('vehicle_id, route_id and assignment_date are required.', 400);
            return $this->response();
        }

        $data = [
            'vehicle_id' => (int) $vehicleId,
            'route_id' => (int) $routeId,
            'assignment_date' => $assignmentDate,
            'driver_id' => $driverId !== '' ? (int) $driverId : null,
            'shift' => $shift !== '' ? $shift : null,
            'planned_start_time' => $plannedStartTime !== '' ? $plannedStartTime : null,
            'planned_end_time' => $plannedEndTime !== '' ? $plannedEndTime : null,
            'assignment_status' => $assignmentStatus !== '' ? $assignmentStatus : 'SCHEDULED',
        ];

        $model->update($assignmentId, $data);
        $updated = $model->find($assignmentId);
        $this->setSuccess('Route assignment updated successfully.');
        $this->setOutput($updated);
        return $this->response();
    }

    public function delete($id)
    {
        if (! $this->isPost()) { $this->setError($this->methodNotAllowed, 405); return $this->response(); }
        if (! $this->AuthenticateApikey()) { $this->setError($this->invalidApiKey, 401); return $this->response(); }
        if (! $this->AuthenticateToken()) { $this->setError($this->invalidToken, 401); return $this->response(); }

        $assignmentId = (int) $id;
        if ($assignmentId < 1) { $this->setError('Invalid assignment id.', 400); return $this->response(); }

        $model = new VehicleRouteAssignmentsModel();
        $row = $model->find($assignmentId);
        if (! $row) { $this->setError('Route assignment not found.', 404); return $this->response(); }

        $model->delete($assignmentId);
        $this->setSuccess('Route assignment deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
