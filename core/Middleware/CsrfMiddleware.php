<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Exceptions\HttpException;
use Core\Request;
use Core\Response;
use Core\Session;

class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $this->session->start();

        // Gera token se não existir
        if (!$this->session->has('_csrf_token')) {
            $this->session->set('_csrf_token', bin2hex(random_bytes(32)));
        }

        // Ignora métodos safe (GET, HEAD, OPTIONS) e rotas /api/*
        if ($this->isReading($request) || $this->isApiRoute($request)) {
            return $next($request, $response);
        }

        // Valida token
        $token = $request->input('_token')
            ?? $request->header('X-CSRF-TOKEN');

        if (!$token || !hash_equals($this->session->get('_csrf_token', ''), $token)) {
            throw new HttpException(419, 'Token CSRF inválido ou ausente.');
        }

        return $next($request, $response);
    }

    private function isReading(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function isApiRoute(Request $request): bool
    {
        return str_starts_with($request->path(), '/api');
    }
}
