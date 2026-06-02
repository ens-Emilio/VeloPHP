<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;
use Core\Database\Connection;
use Core\Database\Migrator;

class MigrateRollbackCommand extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'migrate:rollback';
    }

    public function description(): string
    {
        return 'Reverte o ultimo batch de migrations';
    }

    public function handle(array $args): int
    {
        $pretend = in_array('--pretend', $args, true);

        $basePath = $this->console->basePath();
        $app = new \Core\App(basePath: $basePath);
        $connection = $app->container()->make(Connection::class);
        $migrator = new Migrator($connection, $basePath . '/database/migrations');

        if ($pretend) {
            $this->info('Modo --pretend: rollback seria executado.');
            return 0;
        }

        $rolledBack = $migrator->rollback();

        if (empty($rolledBack)) {
            $this->info('Nenhuma migration para reverter.');
            return 0;
        }

        $this->info('Migrations revertidas:');
        foreach ($rolledBack as $file) {
            $this->line("  - {$file}");
        }

        return 0;
    }
}
