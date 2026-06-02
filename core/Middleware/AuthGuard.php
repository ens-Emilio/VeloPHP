<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Auth\Auth;
use Core\Request;
use Core\Response;

class AuthGuard implements MiddlewareInterface
{
    public function __construct(
        private readonly Auth $auth,
    ) {
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        if ($this->auth->guest()) {
            // API: retorna 401 JSON
            if (str_starts_with($request->path(), '/api')) {
                return $response->json([
                    'error' => 'Unauthorized',
                    'message' => 'Autenticação necessária.',
                ], 401);
            }

            // Web: redireciona para login
            return $response->redirect('/login', 302);
        }

        return $next($request, $response);
    }
}
