<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class MakeMigrationCommand extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'make:migration';
    }

    public function description(): string
    {
        return 'Cria um novo arquivo de migration';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error('Uso: php velo make:migration <nome>');
            return 1;
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";

        $path = $this->console->basePath() . '/database/migrations/' . $filename;

        if (file_exists($path)) {
            $this->error("Migration [{$filename}] ja existe!");
            return 1;
        }

        $className = $this->inferClassName($name);
        $content = $this->generateContent($name, $className);

        file_put_contents($path, $content);
        $this->info("Migration criada: database/migrations/{$filename}");

        return 0;
    }

    private function inferClassName(string $name): string
    {
        $parts = array_map('ucfirst', explode('_', $name));
        return implode('', $parts);
    }

    private function generateContent(string $name, string $className): string
    {
        $isCreate = str_contains($name, 'create_') && str_ends_with($name, '_table');
        $isRemove = str_contains($name, 'remove_');

        if ($isCreate) {
            preg_match('/create_(.*)_table/', $name, $matches);
            $table = $matches[1] ?? 'table';

            return <<<PHP
            <?php

            declare(strict_types=1);

            use Core\Database\Schema\Migration;
            use Core\Database\Schema\Blueprint;
            use Core\Database\Schema\Schema;

            class {$className} extends Migration
            {
                public function up(): void
                {
                    Schema::create('{$table}', function (Blueprint \$table) {
                        \$table->id();
                        \$table->timestamps();
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('{$table}');
                }
            }
            PHP;
        }

        if ($isRemove) {
            preg_match('/remove_(.*)_from_(.*)/', $name, $matches);
            $column = $matches[1] ?? 'column';
            $table = $matches[2] ?? 'table';

            return <<<PHP
            <?php

            declare(strict_types=1);

            use Core\Database\Schema\Migration;
            use Core\Database\Schema\Blueprint;
            use Core\Database\Schema\Schema;

            class {$className} extends Migration
            {
                public function up(): void
                {
                    // ATENCAO: Operacao destrutiva!
                    // Descomente apenas se tiver certeza de que nao ha dados/dependencias.
                    // Schema::table('{$table}', function (Blueprint \$table) {
                    //     \$table->dropColumn('{$column}');
                    // });
                }

                public function down(): void
                {
                    // Schema::table('{$table}', function (Blueprint \$table) {
                    //     \$table->string('{$column}');
                    // });
                }
            }
            PHP;
        }

        return <<<PHP
        <?php

        declare(strict_types=1);

        use Core\Database\Schema\Migration;
        use Core\Database\Schema\Blueprint;
        use Core\Database\Schema\Schema;

        class {$className} extends Migration
        {
            public function up(): void
            {
                // Schema::table('table', function (Blueprint \$table) {
                //     \$table->string('column');
                // });
            }

            public function down(): void
            {
                // Schema::table('table', function (Blueprint \$table) {
                //     \$table->dropColumn('column');
                // });
            }
        }
        PHP;
    }
}
