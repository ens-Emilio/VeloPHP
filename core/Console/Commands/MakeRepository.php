<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class MakeRepository extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'make:repository';
    }

    public function description(): string
    {
        return 'Cria um novo repository';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error('Uso: php velo make:repository <NomeRepository>');
            return 1;
        }

        if (!str_ends_with($name, 'Repository')) {
            $name .= 'Repository';
        }

        // Deriva nome da tabela: UserRepository → users
        $entity = str_replace('Repository', '', $name);
        $table = strtolower($entity) . 's';

        $dir = $this->console->basePath() . '/app/Repositories';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Repository [{$name}] já existe!");
            return 1;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Repositories;

        use Core\Database\Repository;

        class {$name} extends Repository
        {
            protected string \$table = '{$table}';
            protected string \$primaryKey = 'id';
        }
        PHP;

        file_put_contents($path, $content);
        $this->info("Repository criado: app/Repositories/{$name}.php");

        return 0;
    }
}
