<?php

declare(strict_types=1);

use App\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| Rotas API
|--------------------------------------------------------------------------
|
| Todas as rotas aqui são automaticamente prefixadas com /api
| e passam pelo middleware de CORS.
|
*/

$router->get('/status', [ApiController::class, 'status'])
    ->name('api.status');

$router->get('/users/{id}', [ApiController::class, 'showUser'])
    ->name('api.users.show');

$router->post('/data', function ($request, $response) {
    return $response->json([
        'received' => true,
        'input' => $request->allInput(),
    ]);
})->name('api.data');
