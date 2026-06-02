<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class RouteList extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'route:list';
    }

    public function description(): string
    {
        return 'Lista todas as rotas registradas';
    }

    public function handle(array $args): int
    {
        $basePath = $this->console->basePath();

        // Bootstrap da aplicação para carregar rotas
        $app = new \Core\App(basePath: $basePath);
        $app->bootProviders();
        $app->loadRoutes($basePath);

        $routes = $app->router()->getRoutes();

        if (empty($routes)) {
            $this->warn('Nenhuma rota registrada.');
            return 0;
        }

        $rows = [];
        foreach ($routes as $route) {
            $middleware = !empty($route->middleware)
                ? implode(', ', array_map(fn ($m) => is_string($m) ? class_basename($m) : 'Closure', $route->middleware))
                : '-';

            $rows[] = [
                $route->method,
                $route->path,
                $route->name ?? '-',
                $middleware,
            ];
        }

        $this->table(['Method', 'Path', 'Name', 'Middleware'], $rows);

        return 0;
    }
}

function class_basename(string $class): string
{
    return basename(str_replace('\\', '/', $class));
}
