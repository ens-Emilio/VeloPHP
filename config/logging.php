<?php

declare(strict_types=1);

return [
    'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
    'path' => dirname(__DIR__) . '/storage/logs',
];
