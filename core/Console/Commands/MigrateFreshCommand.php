<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;
use Core\Database\Connection;
use Core\Database\Migrator;

class MigrateFreshCommand extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'migrate:fresh';
    }

    public function description(): string
    {
        return 'Drop todas as tabelas e executa migrations do zero';
    }

    public function handle(array $args): int
    {
        $seed = in_array('--seed', $args, true);

        $basePath = $this->console->basePath();
        $app = new \Core\App(basePath: $basePath);
        $connection = $app->container()->make(Connection::class);
        $migrator = new Migrator($connection, $basePath . '/database/migrations');

        $this->info('Limpando banco de dados...');
        $migrator->fresh();

        $this->info('Migrations executadas com sucesso!');

        if ($seed) {
            $seederFile = $basePath . '/database/seeders/DatabaseSeeder.php';

            if (file_exists($seederFile)) {
                require $seederFile;
                $seeder = new \Database\Seeders\DatabaseSeeder();
                $seeder->run();
                $this->info('Seeders executados com sucesso!');
            }
        }

        return 0;
    }
}
