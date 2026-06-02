<?php

declare(strict_types=1);

use App\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Rotas Web
|--------------------------------------------------------------------------
*/

$router->get('/', fn ($request, $response) => view('welcome', [
    'title' => 'Bem-vindo',
    'name' => config('app.name'),
]))->name('home');

$router->get('/about', [HomeController::class, 'about'])
    ->name('about');
