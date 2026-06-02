<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Request;
use Core\Response;

interface MiddlewareInterface
{
    /**
     * Processa o request e chama $next para continuar o pipeline.
     */
    public function handle(Request $request, Response $response, callable $next): Response;
}
