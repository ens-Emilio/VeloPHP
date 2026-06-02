<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Config;
use Core\Request;
use Core\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $response->setHeader('Access-Control-Allow-Origin', $this->config->get('cors.origin', '*'));
        $response->setHeader('Access-Control-Allow-Methods', $this->config->get('cors.methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS'));
        $response->setHeader('Access-Control-Allow-Headers', $this->config->get('cors.headers', 'Content-Type, Authorization'));

        if ($request->method() === 'OPTIONS') {
            return $response->setStatus(204);
        }

        return $next($request, $response);
    }
}
