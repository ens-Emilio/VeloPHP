<?php

declare(strict_types=1);

use Core\App;

/*
|--------------------------------------------------------------------------
| Bootstrap da Aplicação
|--------------------------------------------------------------------------
|
| Cria a instância do App, carrega config e registra service providers.
| Este arquivo é incluído pelo front controller (public/index.php).
|
*/

$app = new App(basePath: dirname(__DIR__));

// Registra e inicializa service providers definidos em config/app.php
$app->bootProviders();

return $app;
