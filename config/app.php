<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'VeloPHP',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',

    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],
];
