<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class MakeController extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'make:controller';
    }

    public function description(): string
    {
        return 'Cria um novo controller';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error('Uso: php velo make:controller <NomeController>');
            return 1;
        }

        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $path = $this->console->basePath() . '/app/Controllers/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Controller [{$name}] já existe!");
            return 1;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Controllers;

        use Core\Request;
        use Core\Response;

        class {$name}
        {
            public function index(Request \$request, Response \$response): Response
            {
                return \$response->json([
                    'message' => '{$name} funcionando!',
                ]);
            }
        }
        PHP;

        file_put_contents($path, $content);
        $this->info("Controller criado: app/Controllers/{$name}.php");

        return 0;
    }
}
