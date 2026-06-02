<?php

declare(strict_types=1);

return [
    'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 120), // minutos
    'path' => '/',
    'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'httponly' => true,
    'samesite' => 'Lax',
];
