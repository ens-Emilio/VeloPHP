<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class MakeProvider extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'make:provider';
    }

    public function description(): string
    {
        return 'Cria um novo service provider';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error('Uso: php velo make:provider <NomeProvider>');
            return 1;
        }

        if (!str_ends_with($name, 'Provider') && !str_ends_with($name, 'ServiceProvider')) {
            $name .= 'ServiceProvider';
        }

        $path = $this->console->basePath() . '/app/Providers/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Provider [{$name}] já existe!");
            return 1;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Providers;

        use Core\ServiceProvider;

        class {$name} extends ServiceProvider
        {
            public function register(): void
            {
                // Registre bindings aqui.
            }

            public function boot(): void
            {
                // Executado após todos os providers terem sido registrados.
            }
        }
        PHP;

        file_put_contents($path, $content);
        $this->info("Provider criado: app/Providers/{$name}.php");

        return 0;
    }
}
