<?php

namespace App\Libraries;
require APPPATH.'/ThirdParty/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtLib
{
    protected $secretKey;
    protected $algo;

    public function __construct()
    {
        $AppConfig = new \Config\AppConfig();
        $this->secretKey = $AppConfig->jwt_secret;
        // Algorithm used for signing token
        $this->algo = 'HS256';
    }

    /**
     * Generate JWT token with payload data
     *
     * @param array $payload
     * @param int $expiryInSeconds (optional) token expiry time in seconds
     * @return string JWT token
     */
    public function generateToken(array $payload, int $expiryInSeconds = 3600): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $expiryInSeconds;

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire,
        ]);

        return JWT::encode($tokenPayload, $this->secretKey, $this->algo);
    }

    /**
     * Validate JWT token and return payload data if valid
     *
     * @param string $token
     * @return object|false Payload object or false if invalid
     */
    public function validateToken(string $token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algo));
            return $decoded;
        } catch (Exception $e) {
            // You can log the error here if needed
            return false;
        }
    }
}
