<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class MakeListener extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'make:listener';
    }

    public function description(): string
    {
        return 'Cria um novo event listener';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error('Uso: php velo make:listener <NomeListener>');
            return 1;
        }

        if (!str_ends_with($name, 'Listener')) {
            $name .= 'Listener';
        }

        $path = $this->console->basePath() . '/app/Listeners/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Listener [{$name}] ja existe!");
            return 1;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Listeners;

        class {$name}
        {
            public function handle(mixed \$data): void
            {
                // Logica do listener aqui.
            }
        }
        PHP;

        file_put_contents($path, $content);
        $this->info("Listener criado: app/Listeners/{$name}.php");

        return 0;
    }
}
