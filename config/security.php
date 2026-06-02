<?php

declare(strict_types=1);

return [
    'headers' => [
        'x_content_type_options' => 'nosniff',
        'x_frame_options' => 'SAMEORIGIN',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
    ],

    'hsts' => [
        'enabled' => (bool) ($_ENV['HSTS_ENABLED'] ?? false),
        'max_age' => 31536000,
        'include_subdomains' => true,
    ],

    // Content-Security-Policy (vazio = não enviado)
    'content_security_policy' => $_ENV['CSP_POLICY'] ?? '',

    // Permissions-Policy (vazio = não enviado)
    'permissions_policy' => $_ENV['PERMISSIONS_POLICY'] ?? '',
];
