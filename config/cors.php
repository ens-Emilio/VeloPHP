<?php

declare(strict_types=1);

return [
    'origin' => $_ENV['CORS_ORIGIN'] ?? '*',
    'methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
    'headers' => 'Content-Type, Authorization',
];
