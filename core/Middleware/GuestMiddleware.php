<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Auth\Auth;
use Core\Request;
use Core\Response;

class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Auth $auth,
    ) {
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        if ($this->auth->check()) {
            return $response->redirect('/', 302);
        }

        return $next($request, $response);
    }
}
