<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth\Auth;
use Core\Middleware\MiddlewareInterface;
use Core\Request;
use Core\Response;

/**
 * Middleware de autenticação para rotas API via Bearer token.
 * Para rotas web, use Core\Middleware\AuthGuard (session-based).
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Auth $auth,
    ) {
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Primeiro verifica sessão (web)
        if ($this->auth->check()) {
            return $next($request, $response);
        }

        // Depois verifica Bearer token (API)
        $token = $request->bearerToken();

        if ($token === null) {
            return $response->json([
                'error' => 'Unauthorized',
                'message' => 'Autenticação necessária.',
            ], 401);
        }

        // TODO: Validar token (JWT, database token, etc.)
        // Exemplo:
        // $user = DB::table('personal_access_tokens')->where('token', hash('sha256', $token))->first();
        // if (!$user) { return $response->json(['error' => 'Token inválido'], 401); }
        // $this->auth->login($user);

        return $next($request, $response);
    }
}
