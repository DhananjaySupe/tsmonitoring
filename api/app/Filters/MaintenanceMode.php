<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\AppConfig;
use Config\Services;

class MaintenanceMode implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = new AppConfig();

        if (! empty($config->maintenanceMode) && $config->maintenanceMode === true) {
            $response = Services::response();
            $response->setStatusCode(503);
            $response->setJSON([
                'success' => false,
                'message' => $config->maintenanceMessage ?? 'Service is under maintenance. Please try again later.',
            ]);

            return $response;
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}

