<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class RouteCache extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'route:cache';
    }

    public function description(): string
    {
        return 'Gera cache das rotas para produção';
    }

    public function handle(array $args): int
    {
        $basePath = $this->console->basePath();

        // Bootstrap app para carregar rotas
        /** @var \Core\App $app */
        $app = require $basePath . '/bootstrap/app.php';
        $app->loadRoutes($basePath);

        $router = $app->router();
        $result = $router->exportRoutes();

        $cacheDir = $basePath . '/storage/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/routes.php';
        $content = '<?php return ' . var_export($result['routes'], true) . ';' . PHP_EOL;

        file_put_contents($cacheFile, $content);

        $count = count($result['routes']);
        $this->info("Cache de rotas gerado! {$count} rota(s) cacheadas.");

        if ($result['skipped'] > 0) {
            $this->warn("{$result['skipped']} rota(s) com closure ignorada(s) (não serializáveis).");
        }

        return 0;
    }
}
