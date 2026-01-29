<?php

namespace App\Controllers;

use App\Models\UsersModel;

class Users extends BaseController
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
        if (! $this->checkUserTypePermissions('users:view')) {
            return $this->response();
        }

        $userModel = new UsersModel();
        $page      = (int) $this->getParam('page', 1);
        $length    = (int) $this->getParam('per_page', 25);
        $keywords  = $this->getParam('keywords', '');
        $userType  = $this->getParam('user_type_id', '');
        $isActive  = $this->getParam('is_active', '');
        $orderCol  = $this->getParam('order_by_col', 'user_id');
        $orderDir  = $this->getParam('order_by', 'DESC');

        $params = [
            'keywords'   => $keywords,
            'user_type_id' => $userType,
            'is_active'  => $isActive,
        ];
        $params['count'] = true;
        $totalRecords   = $userModel->getList($params);
        unset($params['count']);

        $paging = paging($page, $totalRecords, $length);
        $params['limit'] = ['length' => $paging['length'], 'offset' => $paging['offset']];
        $params['sort']  = ['column' => $orderCol, 'order' => $orderDir];

        $users = $userModel->getList($params);
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($users));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'users' => $users]);
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
        if (! $this->checkUserTypePermissions('users:view')) {
            return $this->response();
        }

        $userId = (int) $id;
        if ($userId < 1) {
            $this->setError('Invalid user id.', 400);
            return $this->response();
        }

        $userModel = new UsersModel();
        $user      = $userModel->getForView($userId);
        if (! $user) {
            $this->setError('User not found.', 404);
            return $this->response();
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput($user);
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
        if (! $this->checkUserTypePermissions('users:create')) {
            return $this->response();
        }

        $phone      = $this->getPost('phone', '');
        $password   = $this->getPost('password', '');
        $fullName   = $this->getPost('full_name', '');
        $email      = $this->getPost('email', '');
        $userTypeId = $this->getPost('user_type_id', 0);
        $vendorId   = $this->getPost('vendor_id', null);
        $isActive   = $this->getPost('is_active', 1);

        if ($phone === '' || $password === '' || $fullName === '') {
            $this->setError('Phone, password and full_name are required.', 400);
            return $this->response();
        }
        if (strlen($password) < 6) {
            $this->setError('Password must be at least 6 characters.', 400);
            return $this->response();
        }

        $userModel = new UsersModel();
        $existing  = $userModel->where('phone', $phone)->first();
        if ($existing) {
            $this->setError('Phone already exists.', 409);
            return $this->response();
        }
        if ($email !== '' && $userModel->where('email', $email)->first()) {
            $this->setError('Email already exists.', 409);
            return $this->response();
        }

        $data = [
            'phone'        => $phone,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'full_name'    => $fullName,
            'email'        => $email ?: null,
            'user_type_id' => (int) $userTypeId,
            'vendor_id'    => $vendorId !== null && $vendorId !== '' ? (int) $vendorId : null,
            'is_active'    => (int) $isActive,
        ];

        $userId = $userModel->insert($data, true);
        if (! $userId) {
            $this->setError('Failed to create user.', 500);
            return $this->response();
        }

        $user = $userModel->getForView($userId);
        $this->setSuccess('User created successfully.');
        $this->setOutput($user);
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
        if (! $this->checkUserTypePermissions('users:edit')) {
            return $this->response();
        }

        $userId = (int) $id;
        if ($userId < 1) {
            $this->setError('Invalid user id.', 400);
            return $this->response();
        }

        $userModel = new UsersModel();
        $user      = $userModel->find($userId);
        if (! $user) {
            $this->setError('User not found.', 404);
            return $this->response();
        }

        $fullName   = $this->getPost('full_name', $user['full_name'] ?? '');
        $email      = $this->getPost('email', $user['email'] ?? '');
        $phone      = $this->getPost('phone', $user['phone'] ?? '');
        $userTypeId = $this->getPost('user_type_id', $user['user_type_id'] ?? 0);
        $vendorId   = $this->getPost('vendor_id', $user['vendor_id'] ?? null);
        $isActive   = $this->getPost('is_active', $user['is_active'] ?? 1);
        $password   = $this->getPost('password', '');

        if ($phone === '' || $fullName === '') {
            $this->setError('Phone and full_name are required.', 400);
            return $this->response();
        }
        if (strlen($password) > 0 && strlen($password) < 6) {
            $this->setError('Password must be at least 6 characters.', 400);
            return $this->response();
        }

        $existing = $userModel->where('phone', $phone)->where('user_id !=', $userId)->first();
        if ($existing) {
            $this->setError('Phone already exists.', 409);
            return $this->response();
        }
        if ($email !== '' && $userModel->where('email', $email)->where('user_id !=', $userId)->first()) {
            $this->setError('Email already exists.', 409);
            return $this->response();
        }

        $data = [
            'full_name'    => $fullName,
            'email'        => $email ?: null,
            'phone'        => $phone ?: null,
            'user_type_id' => (int) $userTypeId,
            'vendor_id'    => $vendorId !== null && $vendorId !== '' ? (int) $vendorId : null,
            'is_active'    => (int) $isActive,
        ];
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $userModel->update($userId, $data);
        $updated = $userModel->getForView($userId);
        $this->setSuccess('User updated successfully.');
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
        if (! $this->requireAdminOrSuperAdmin()) {
            return $this->response();
        }

        $userId = (int) $id;
        if ($userId < 1) {
            $this->setError('Invalid user id.', 400);
            return $this->response();
        }

        $currentUserId = (int) ($this->_member['id'] ?? 0);
        if ($userId === $currentUserId) {
            $this->setError('You cannot delete your own account.', 400);
            return $this->response();
        }

        $userModel = new UsersModel();
        $user      = $userModel->find($userId);
        if (! $user) {
            $this->setError('User not found.', 404);
            return $this->response();
        }

        $userModel->delete($userId);
        $this->setSuccess('User deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}
