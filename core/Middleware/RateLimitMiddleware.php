<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Cache\CacheInterface;
use Core\Exceptions\HttpException;
use Core\Request;
use Core\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $maxAttempts = 60,
        private readonly int $windowSeconds = 60,
    ) {
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $key = 'rate_limit:' . $this->resolveIp($request);

        $attempts = (int) ($this->cache->get($key, 0));

        if ($attempts >= $this->maxAttempts) {
            $response->setHeader('Retry-After', (string) $this->windowSeconds);
            $response->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
            $response->setHeader('X-RateLimit-Remaining', '0');
            throw new HttpException(429, 'Too Many Requests. Tente novamente em breve.');
        }

        $this->cache->set($key, $attempts + 1, $this->windowSeconds);

        $response->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', (string) ($this->maxAttempts - $attempts - 1));

        return $next($request, $response);
    }

    private function resolveIp(Request $request): string
    {
        return $request->header('x-forwarded-for')
            ?? $request->server('REMOTE_ADDR')
            ?? '127.0.0.1';
    }
}
