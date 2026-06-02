<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class MakeMiddleware extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'make:middleware';
    }

    public function description(): string
    {
        return 'Cria um novo middleware';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error('Uso: php velo make:middleware <NomeMiddleware>');
            return 1;
        }

        if (!str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        $path = $this->console->basePath() . '/app/Middleware/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Middleware [{$name}] já existe!");
            return 1;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Middleware;

        use Core\Middleware\MiddlewareInterface;
        use Core\Request;
        use Core\Response;

        class {$name} implements MiddlewareInterface
        {
            public function handle(Request \$request, Response \$response, callable \$next): Response
            {
                // Lógica antes do handler

                \$response = \$next(\$request, \$response);

                // Lógica depois do handler

                return \$response;
            }
        }
        PHP;

        file_put_contents($path, $content);
        $this->info("Middleware criado: app/Middleware/{$name}.php");

        return 0;
    }
}
