<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Middleware\CorsMiddleware;
use Core\Middleware\CsrfMiddleware;
use Core\Middleware\SecurityHeadersMiddleware;

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

/** @var Core\App $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';
$router = $app->router();

/*
|--------------------------------------------------------------------------
| Middleware Global
|--------------------------------------------------------------------------
*/

$router->use(CorsMiddleware::class);
$router->use(CsrfMiddleware::class);
$router->use(SecurityHeadersMiddleware::class);

/*
|--------------------------------------------------------------------------
| Rotas
|--------------------------------------------------------------------------
*/

$app->loadRoutes(dirname(__DIR__));

/*
|--------------------------------------------------------------------------
| Executa
|--------------------------------------------------------------------------
*/

$app->run();
