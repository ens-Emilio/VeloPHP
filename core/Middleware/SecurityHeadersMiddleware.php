<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Config;
use Core\Request;
use Core\Response;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $response = $next($request, $response);

        $headers = $this->config->get('security.headers', []);

        $response->setHeader('X-Content-Type-Options', $headers['x_content_type_options'] ?? 'nosniff');
        $response->setHeader('X-Frame-Options', $headers['x_frame_options'] ?? 'SAMEORIGIN');
        $response->setHeader('X-XSS-Protection', $headers['x_xss_protection'] ?? '1; mode=block');
        $response->setHeader('Referrer-Policy', $headers['referrer_policy'] ?? 'strict-origin-when-cross-origin');

        if ($this->config->get('security.hsts.enabled', false)) {
            $maxAge = $this->config->get('security.hsts.max_age', 31536000);
            $value = 'max-age=' . $maxAge;
            if ($this->config->get('security.hsts.include_subdomains', true)) {
                $value .= '; includeSubDomains';
            }
            $response->setHeader('Strict-Transport-Security', $value);
        }

        $csp = $this->config->get('security.content_security_policy', '');
        if ($csp !== '') {
            $response->setHeader('Content-Security-Policy', $csp);
        }

        $permissions = $this->config->get('security.permissions_policy', '');
        if ($permissions !== '') {
            $response->setHeader('Permissions-Policy', $permissions);
        }

        return $response;
    }
}
