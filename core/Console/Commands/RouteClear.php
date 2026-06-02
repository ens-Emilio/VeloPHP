<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class RouteClear extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'route:clear';
    }

    public function description(): string
    {
        return 'Remove o cache de rotas';
    }

    public function handle(array $args): int
    {
        $cacheFile = $this->console->basePath() . '/storage/cache/routes.php';

        if (!file_exists($cacheFile)) {
            $this->warn('Nenhum cache de rotas encontrado.');
            return 0;
        }

        unlink($cacheFile);
        $this->info('Cache de rotas removido!');

        return 0;
    }
}
