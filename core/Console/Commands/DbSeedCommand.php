<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class DbSeedCommand extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'db:seed';
    }

    public function description(): string
    {
        return 'Executa os seeders do banco de dados';
    }

    public function handle(array $args): int
    {
        $class = null;
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--class=')) {
                $class = substr($arg, 8);
            }
        }

        $basePath = $this->console->basePath();

        if ($class !== null) {
            $path = $basePath . '/database/seeders/' . $class . '.php';
            if (!file_exists($path)) {
                $path = $basePath . '/database/seeders/' . basename(str_replace('\\', '/', $class)) . '.php';
            }
            if (file_exists($path)) {
                require $path;
                $instance = new $class();
                $instance->run();
                $this->info("Seeder [{$class}] executado com sucesso!");
                return 0;
            }
            $this->error("Seeder [{$class}] nao encontrado.");
            return 1;
        }

        $seederFile = $basePath . '/database/seeders/DatabaseSeeder.php';
        if (!file_exists($seederFile)) {
            $this->error('DatabaseSeeder nao encontrado em database/seeders/DatabaseSeeder.php');
            return 1;
        }

        require $seederFile;
        $seeder = new \Database\Seeders\DatabaseSeeder();
        $seeder->run();
        $this->info('Seeders executados com sucesso!');

        return 0;
    }
}
