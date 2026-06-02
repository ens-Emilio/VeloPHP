<?php

declare(strict_types=1);

return [
    'table' => $_ENV['AUTH_TABLE'] ?? 'users',
    'primary_key' => 'id',
    'password_column' => 'password',
];
