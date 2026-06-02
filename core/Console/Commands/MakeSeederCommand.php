<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class MakeSeederCommand extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'make:seeder';
    }

    public function description(): string
    {
        return 'Cria um novo seeder';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error('Uso: php velo make:seeder <NomeSeeder>');
            return 1;
        }

        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $path = $this->console->basePath() . '/database/seeders/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Seeder [{$name}] ja existe!");
            return 1;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace Database\Seeders;

        use Core\Database\Seeder;
        use Core\Database\DB;

        class {$name} extends Seeder
        {
            public function run(): void
            {
                // DB::table('table')->insert([
                //     'column' => 'value',
                // ]);
            }
        }
        PHP;

        file_put_contents($path, $content);
        $this->info("Seeder criado: database/seeders/{$name}.php");

        return 0;
    }
}
