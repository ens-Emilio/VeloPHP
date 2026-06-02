<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Request;
use Core\Response;

class ApiController
{
    public function status(Request $request, Response $response): Response
    {
        return $response->json([
            'status' => 'OK',
            'framework' => 'VeloPHP',
            'timestamp' => time(),
            'php' => PHP_VERSION,
            'environment' => config('app.env'),
        ]);
    }

    public function showUser(Request $request, Response $response, string $id): Response
    {
        return $response->json([
            'user_id' => $id,
            'name' => 'John Doe',
            'source' => 'VeloPHP API',
        ]);
    }
}
