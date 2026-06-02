<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use Core\Middleware\GuestMiddleware;

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação
|--------------------------------------------------------------------------
*/

$router->get('/login', [AuthController::class, 'showLogin'])
    ->name('login')
    ->middleware(GuestMiddleware::class);

$router->post('/login', [AuthController::class, 'login'])
    ->name('login.submit')
    ->middleware(GuestMiddleware::class);

$router->get('/register', [AuthController::class, 'showRegister'])
    ->name('register')
    ->middleware(GuestMiddleware::class);

$router->post('/register', [AuthController::class, 'register'])
    ->name('register.submit')
    ->middleware(GuestMiddleware::class);

$router->post('/logout', [AuthController::class, 'logout'])
    ->name('logout');
