<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\SessionsModel;
use App\Libraries\JwtLib;
use Config\AppConfig;

class Auth extends BaseController
{
    public function login()
    {
        if (! $this->isPost()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }

        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }
        $phone = $this->getPost('phone', '');
        $password = $this->getPost('password', '');

        if (empty($phone) || empty($password)) {
            $this->setError('Phone and password are required.', 400);
            return $this->response();
        }

        $usersModel = new UsersModel();

        $user = $usersModel->where('phone', $phone)->where('is_active', 1)->first();

        if(!$user){
            $this->setError('Invalid credentials.', 401);
            return $this->response();
        }

        if(!password_verify($password, $user['password_hash'])){
            $this->setError('Invalid credentials.', 401);
            return $this->response();
        }

        $sessionsModel = new SessionsModel();
        $jwt        = new JwtLib();

        if (! empty($this->AppConfig->single_login)) {
            $sessionsModel->where('user_id', $user['user_id'])->delete();
        }

        $tokenPayload = [
            'user_id' => $user['user_id'],
        ];

        $accessToken = $jwt->generateToken($tokenPayload);

        $sessionsModel->insert([
            'user_id'       => $user['user_id'],
            'session_token' => $accessToken,
            'logged_in'     => date('Y-m-d H:i:s'),
            'status'        => 1,
        ]);

        $twoFactorRequired = false;
        if (! empty($this->AppConfig->twoFactorAuth['enabled'])) {
            if (function_exists('sendOtp')) {
                sendOtp($user['user_id']);
                $twoFactorRequired = true;
            }
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput([
            'access_token'      => $twoFactorRequired ? null : $accessToken,
            'two_factor_required' => $twoFactorRequired,
            'user' => [
                'user_id'      => $user['user_id'],
                'code'         => $user['code'],
                'email'        => $user['email'],
                'phone'        => $user['phone'],
                'full_name'    => $user['full_name'],
                'user_type_id' => $user['user_type_id'],
                'vendor_id'    => $user['vendor_id'],
            ],
        ]);

        return $this->response();
    }

    public function logout()
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

        $sessionRow = $this->_session;

        if (! empty($sessionRow['session_id'])) {
            $sessionsModel = new SessionsModel();
            $sessionsModel->update($sessionRow['session_id'], [
                'status'     => 0,
                'logged_out' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->setSuccess('Logged out successfully.');
        $this->setOutput(json_decode('{}', true));

        return $this->response();
    }

    public function forgotPassword()
    {
        if (! $this->isPost()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }

        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }

        $identifier = $this->getPost('phone', '');

        if (empty($identifier)) {
            $this->setError('Phone is required.', 400);
            return $this->response();
        }

        $usersModel = new UsersModel();

        $user = $usersModel->where('phone', $identifier)->where('is_active', 1)->first();

        if ($user) {
            if (! empty($this->AppConfig->twoFactorAuth['enabled']) && function_exists('sendOtp')) {
                sendOtp($user['user_id']);
            }
        }

        $this->setSuccess('If the account exists, an OTP has been sent.');
        $this->setOutput(json_decode('{}', true));

        return $this->response();
    }

    public function register()
    {
        if (! $this->isPost()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }

        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }

        $phone     = $this->getPost('phone', '');
        $password  = $this->getPost('password', '');
        $fullName  = $this->getPost('full_name', '');
        $email     = $this->getPost('email', '');
        $vendorId  = $this->getPost('vendor_id', null);
        $userType  = $this->getPost('user_type_id', null);

        if (empty($phone) || empty($password) || empty($fullName)) {
            $this->setError('Phone, password and full_name are required.', 400);
            return $this->response();
        }

        if (strlen($password) < 6) {
            $this->setError('Password must be at least 6 characters.', 400);
            return $this->response();
        }

        $usersModel = new UsersModel();

        $existing = $usersModel->where('phone', $phone)->first();
        if ($existing) {
            $this->setError('An account with this phone already exists.', 409);
            return $this->response();
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $data = [
            'code'         => $this->AppConfig->userCodePrefix . rand(100000, 999999),
            'password_hash'=> $passwordHash,
            'email'        => $email,
            'phone'        => $phone,
            'full_name'    => $fullName,
            'user_type_id' => $userType ?? 0,
            'vendor_id'    => $vendorId ?? null,
            'is_active'    => 1,
        ];

        $userId = $usersModel->insert($data, true);

        if (! $userId) {
            $this->setError('Unable to register user. Please try again.', 500);
            return $this->response();
        }

        $user = $usersModel->find($userId);

        $twoFactorRequired = false;
        if (! empty($this->AppConfig->twoFactorAuth['enabled']) && function_exists('sendOtp')) {
            sendOtp($userId);
            $twoFactorRequired = true;
        }

        $this->setSuccess('Registered successfully.');
        $this->setOutput([
            'two_factor_required' => $twoFactorRequired,
            'user' => [
                'user_id'      => $user['user_id'],
                'code'         => $user['code'] ?? null,
                'email'        => $user['email'],
                'phone'        => $user['phone'],
                'full_name'    => $user['full_name'],
                'user_type_id' => $user['user_type_id'],
                'vendor_id'    => $user['vendor_id'],
            ],
        ]);

        return $this->response();
    }

    public function verifyOtp()
    {
        if (! $this->isPost()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }

        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }

        $phone = $this->getPost('phone', '');
        $otp   = $this->getPost('otp', '');

        if (empty($phone) || empty($otp)) {
            $this->setError('Phone and OTP are required.', 400);
            return $this->response();
        }

        $usersModel = new UsersModel();

        $user = $usersModel->where('phone', $phone)->where('is_active', 1)->first();

        if (! $user) {
            $this->setError('Invalid OTP or expired.', 400);
            return $this->response();
        }

        $attempts    = (int) ($user['otp_attempts'] ?? 0);

        if ($attempts >= $this->AppConfig->maxOtpAttempts) {
            $this->setError('Maximum OTP attempts exceeded. Please request a new OTP.', 429);
            return $this->response();
        }

        $now = date('Y-m-d H:i:s');

        if (empty($user['otp']) || (string) $user['otp'] !== (string) $otp || empty($user['otp_expiry']) || $user['otp_expiry'] < $now) {
            $usersModel->update($user['user_id'], [
                'otp_attempts' => $attempts + 1,
            ]);

            $this->setError('Invalid OTP or expired.', 400);
            return $this->response();
        }

        $usersModel->update($user['user_id'], [
            'otp'          => null,
            'otp_expiry'   => null,
            'otp_attempts' => 0,
        ]);

        $sessionsModel = new SessionsModel();
        $jwt        = new JwtLib();

        $sessionRow = $sessionsModel
            ->where('user_id', $user['user_id'])
            ->where('status', 1)
            ->orderBy('logged_in', 'DESC')
            ->first();

        if ($sessionRow) {
            $accessToken = $sessionRow['session_token'];
        } else {
            if (! empty($this->AppConfig->single_login)) {
                $sessionsModel->where('user_id', $user['user_id'])->delete();
            }

            $tokenPayload = [
                'user_id' => $user['user_id'],
            ];

            $accessToken = $jwt->generateToken($tokenPayload);

            $sessionsModel->insert([
                'user_id'       => $user['user_id'],
                'session_token' => $accessToken,
                'logged_in'     => date('Y-m-d H:i:s'),
                'status'        => 1,
            ]);
        }

        $this->setSuccess('OTP verified successfully.');
        $this->setOutput([
            'access_token' => $accessToken,
            'user' => [
                'user_id'      => $user['user_id'],
                'code'         => $user['code'],
                'email'        => $user['email'],
                'phone'        => $user['phone'],
                'full_name'    => $user['full_name'],
                'user_type_id' => $user['user_type_id'],
                'vendor_id'    => $user['vendor_id'],
            ],
        ]);

        return $this->response();
    }
}

