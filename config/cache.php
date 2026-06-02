<?php

declare(strict_types=1);

return [
    'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
    'path' => dirname(__DIR__) . '/storage/cache',
    'ttl' => (int) ($_ENV['CACHE_TTL'] ?? 3600), // segundos
];
