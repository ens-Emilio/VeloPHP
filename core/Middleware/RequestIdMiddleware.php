<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Request;
use Core\Response;

class RequestIdMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        $requestId = $request->header('x-request-id') ?? self::generateId();

        $request = $request->withHeader('X-Request-Id', $requestId);

        $response = $next($request, $response);

        $response->setHeader('X-Request-Id', $requestId);

        return $response;
    }

    public static function generateId(): string
    {
        $data = random_bytes(16);
        // UUID v4
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
