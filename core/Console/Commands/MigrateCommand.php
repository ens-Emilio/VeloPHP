<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;
use Core\Database\Connection;
use Core\Database\Migrator;

class MigrateCommand extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Executa as migrations pendentes';
    }

    public function handle(array $args): int
    {
        $pretend = in_array('--pretend', $args, true);

        $basePath = $this->console->basePath();
        $app = new \Core\App(basePath: $basePath);
        $connection = $app->container()->make(Connection::class);
        $migrator = new Migrator($connection, $basePath . '/database/migrations');

        $pending = $migrator->getPending();

        if (empty($pending)) {
            $this->info('Nenhuma migration pendente.');
            return 0;
        }

        $this->line('Migrations pendentes:');
        foreach ($pending as $file) {
            $this->line("  - {$file}");
        }

        if ($pretend) {
            $this->info("\nModo --pretend: migrations seriam executadas.");
            return 0;
        }

        $executed = $migrator->run();

        $this->info("\nMigrations executadas:");
        foreach ($executed as $file) {
            $this->line("  - {$file}");
        }

        return 0;
    }
}
