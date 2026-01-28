<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class InputSanitizer implements FilterInterface
{
    /**
     * Basic input sanitization for API requests.
     * - Trims strings
     * - Strips HTML tags
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $_GET  = $this->sanitizeArray($_GET);
        $_POST = $this->sanitizeArray($_POST);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }

    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $value      = trim($value);
                $value      = strip_tags($value);
                $data[$key] = $value;
            }
        }

        return $data;
    }
}

