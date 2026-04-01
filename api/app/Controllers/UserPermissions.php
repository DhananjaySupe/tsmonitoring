<?php

namespace App\Controllers;

use App\Models\UserTypePermissionsModel;

class UserPermissions extends BaseController
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
        if (! $this->checkUserTypePermissions('user-permissions:view')) {
            return $this->response();
        }

        $model = new UserTypePermissionsModel();
        $page = (int) $this->getParam('page', 1);
        $length = (int) $this->getParam('per_page', 25);
        $userTypeId = $this->getParam('user_type_id', '');
        $permission = $this->getParam('permission', '');
        $orderCol = $this->getParam('order_by_col', 'permission_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $params = [
            'user_type_id' => $userTypeId,
            'permission' => $permission,
        ];
        $params['count'] = true;
        $totalRecords = $model->getList($params);
        unset($params['count']);

        $paging = paging($page, $totalRecords, $length);
        $params['limit'] = ['length' => $paging['length'], 'offset' => $paging['offset']];

        $list = $model->getList($params);
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($list));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'permissions' => $list]);
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
        if (! $this->checkUserTypePermissions('user-permissions:view')) {
            return $this->response();
        }

        $permissionId = (int) $id;
        if ($permissionId < 1) {
            $this->setError('Invalid permission id.', 400);
            return $this->response();
        }

        $model = new UserTypePermissionsModel();
        $row = $model->find($permissionId);
        if (! $row) {
            $this->setError('Permission not found.', 404);
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
        if (! $this->checkUserTypePermissions('user-permissions:create')) {
            return $this->response();
        }

        $userTypeId = $this->getPost('user_type_id', '');
        $permission = $this->getPost('permission', '');
        $canCreate = (int) $this->getPost('can_create', 0);
        $canView   = (int) $this->getPost('can_view', 0);
        $canEdit   = (int) $this->getPost('can_edit', 0);
        $canDelete = (int) $this->getPost('can_delete', 0);

        if ($userTypeId === '' || $permission === '') {
            $this->setError('user_type_id and permission are required.', 400);
            return $this->response();
        }

        $model = new UserTypePermissionsModel();
        $existing = $model->where('user_type_id', (int) $userTypeId)->where('permission', strtoupper($permission))->first();
        if ($existing) {
            $this->setError('Permission for this user type already exists.', 409);
            return $this->response();
        }

        $data = [
            'user_type_id' => (int) $userTypeId,
            'permission' => strtoupper($permission),
            'can_create' => $canCreate ? 1 : 0,
            'can_view'   => $canView ? 1 : 0,
            'can_edit'   => $canEdit ? 1 : 0,
            'can_delete' => $canDelete ? 1 : 0,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create permission.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Permission created successfully.');
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
        if (! $this->checkUserTypePermissions('user-permissions:edit')) {
            return $this->response();
        }

        $permissionId = (int) $id;
        if ($permissionId < 1) {
            $this->setError('Invalid permission id.', 400);
            return $this->response();
        }

        $model = new UserTypePermissionsModel();
        $row = $model->find($permissionId);
        if (! $row) {
            $this->setError('Permission not found.', 404);
            return $this->response();
        }

        $canCreate = (int) $this->getPost('can_create', $row['can_create'] ?? 0);
        $canView   = (int) $this->getPost('can_view', $row['can_view'] ?? 0);
        $canEdit   = (int) $this->getPost('can_edit', $row['can_edit'] ?? 0);
        $canDelete = (int) $this->getPost('can_delete', $row['can_delete'] ?? 0);

        $data = [
            'can_create' => $canCreate ? 1 : 0,
            'can_view'   => $canView ? 1 : 0,
            'can_edit'   => $canEdit ? 1 : 0,
            'can_delete' => $canDelete ? 1 : 0,
        ];

        $model->update($permissionId, $data);
        $updated = $model->find($permissionId);
        $this->setSuccess('Permission updated successfully.');
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
        if (! $this->checkUserTypePermissions('user-permissions:delete')) {
            return $this->response();
        }

        $permissionId = (int) $id;
        if ($permissionId < 1) {
            $this->setError('Invalid permission id.', 400);
            return $this->response();
        }

        $model = new UserTypePermissionsModel();
        $row = $model->find($permissionId);
        if (! $row) {
            $this->setError('Permission not found.', 404);
            return $this->response();
        }

        $model->delete($permissionId);
        $this->setSuccess('Permission deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
