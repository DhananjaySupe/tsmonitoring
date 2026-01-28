<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class RateLimiter implements FilterInterface
{
    // Default values, can be overridden from route:
    // e.g. ['filter' => 'ratelimiter:10,60'] => 10 requests / 60 seconds
    protected int $maxRequests   = 60;    // requests
    protected int $windowSeconds = 60;    // per 60 seconds

    public function before(RequestInterface $request, $arguments = null)
    {
        // Allow per‑route configuration via filter arguments
        if (! empty($arguments)) {
            if (isset($arguments[0]) && is_numeric($arguments[0])) {
                $this->maxRequests = (int) $arguments[0];
            }
            if (isset($arguments[1]) && is_numeric($arguments[1])) {
                $this->windowSeconds = (int) $arguments[1];
            }
        }

        $cache = Services::cache();
        $ip    = $request->getIPAddress();
        $uri   = $request->getUri()->getPath();

        $key = 'rate_limit_' . md5($ip . '|' . $uri);

        $current = $cache->get($key);
        if ($current === null) {
            $cache->save($key, 1, $this->windowSeconds);
            return null;
        }

        if ($current >= $this->maxRequests) {
            $response = Services::response();
            $response->setStatusCode(429);
            $response->setJSON([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
            ]);

            return $response;
        }

        $cache->increment($key);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}

