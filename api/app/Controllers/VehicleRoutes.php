<?php

namespace App\Controllers;

use App\Models\VehicleRoutesModel;

class VehicleRoutes extends BaseController
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

        $model    = new VehicleRoutesModel();
        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $keywords = $this->getParam('keywords', '');
        $status   = $this->getParam('status', '');
        $zone     = $this->getParam('zone', '');
        $orderCol = $this->getParam('order_by_col', 'route_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($keywords !== '') {
            $k = $model->db->escapeLikeString($keywords);
            $builder->groupStart()
                ->like('route_code', $k)
                ->orLike('route_name', $k)
                ->orLike('zone', $k)
                ->groupEnd();
        }
        if ($status !== '') {
            $builder->where('status', $status);
        }
        if ($zone !== '') {
            $builder->where('zone', $zone);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'routes' => $rows]);
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

        $routeId = (int) $id;
        if ($routeId < 1) {
            $this->setError('Invalid route id.', 400);
            return $this->response();
        }

        $model = new VehicleRoutesModel();
        $row   = $model->find($routeId);
        if (! $row) {
            $this->setError('Route not found.', 404);
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

        $routeCode   = $this->getPost('route_code', '');
        $routeName   = $this->getPost('route_name', '');
        $zone        = $this->getPost('zone', '');
        $totalPoints = $this->getPost('total_points', '');
        $estimatedDistance = $this->getPost('estimated_distance', '');
        $estimatedDuration = $this->getPost('estimated_duration', '');
        $status      = $this->getPost('status', 'ACTIVE');

        if ($routeCode === '' || $routeName === '') {
            $this->setError('route_code and route_name are required.', 400);
            return $this->response();
        }

        $data = [
            'route_code'          => $routeCode,
            'route_name'          => $routeName,
            'zone'                => $zone !== '' ? $zone : null,
            'total_points'        => $totalPoints !== '' ? (int) $totalPoints : null,
            'estimated_distance'  => $estimatedDistance !== '' ? (float) $estimatedDistance : null,
            'estimated_duration'  => $estimatedDuration !== '' ? $estimatedDuration : null,
            'status'              => $status !== '' ? $status : 'ACTIVE',
        ];

        $model = new VehicleRoutesModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create route.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Route created successfully.');
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

        $routeId = (int) $id;
        if ($routeId < 1) {
            $this->setError('Invalid route id.', 400);
            return $this->response();
        }

        $model = new VehicleRoutesModel();
        $row   = $model->find($routeId);
        if (! $row) {
            $this->setError('Route not found.', 404);
            return $this->response();
        }

        $routeCode   = $this->getPost('route_code', $row['route_code'] ?? '');
        $routeName   = $this->getPost('route_name', $row['route_name'] ?? '');
        $zone        = $this->getPost('zone', $row['zone'] ?? '');
        $totalPoints = $this->getPost('total_points', $row['total_points'] ?? '');
        $estimatedDistance = $this->getPost('estimated_distance', $row['estimated_distance'] ?? '');
        $estimatedDuration = $this->getPost('estimated_duration', $row['estimated_duration'] ?? '');
        $status      = $this->getPost('status', $row['status'] ?? 'ACTIVE');

        if ($routeCode === '' || $routeName === '') {
            $this->setError('route_code and route_name are required.', 400);
            return $this->response();
        }

        $data = [
            'route_code'          => $routeCode,
            'route_name'          => $routeName,
            'zone'                => $zone !== '' ? $zone : null,
            'total_points'        => $totalPoints !== '' ? (int) $totalPoints : null,
            'estimated_distance'  => $estimatedDistance !== '' ? (float) $estimatedDistance : null,
            'estimated_duration'  => $estimatedDuration !== '' ? $estimatedDuration : null,
            'status'              => $status !== '' ? $status : 'ACTIVE',
        ];

        $model->update($routeId, $data);
        $updated = $model->find($routeId);
        $this->setSuccess('Route updated successfully.');
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

        $routeId = (int) $id;
        if ($routeId < 1) {
            $this->setError('Invalid route id.', 400);
            return $this->response();
        }

        $model = new VehicleRoutesModel();
        $row   = $model->find($routeId);
        if (! $row) {
            $this->setError('Route not found.', 404);
            return $this->response();
        }

        $model->delete($routeId);
        $this->setSuccess('Route deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
