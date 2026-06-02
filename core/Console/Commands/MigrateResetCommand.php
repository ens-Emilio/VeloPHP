<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;
use Core\Database\Connection;
use Core\Database\Migrator;

class MigrateResetCommand extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'migrate:reset';
    }

    public function description(): string
    {
        return 'Reverte todas as migrations';
    }

    public function handle(array $args): int
    {
        $basePath = $this->console->basePath();
        $app = new \Core\App(basePath: $basePath);
        $connection = $app->container()->make(Connection::class);
        $migrator = new Migrator($connection, $basePath . '/database/migrations');

        $rolledBack = $migrator->reset();

        if (empty($rolledBack)) {
            $this->info('Nenhuma migration para reverter.');
            return 0;
        }

        $this->info('Todas as migrations foram revertidas:');
        foreach ($rolledBack as $file) {
            $this->line("  - {$file}");
        }

        return 0;
    }
}
