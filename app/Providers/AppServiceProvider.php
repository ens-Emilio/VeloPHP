<?php

declare(strict_types=1);

namespace App\Providers;

use Core\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registre bindings de serviços da aplicação aqui.
        // Exemplo:
        // $this->app->container()->singleton(MyService::class);
    }

    public function boot(): void
    {
        // Executado após todos os providers terem sido registrados.
    }
}
