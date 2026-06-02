<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

class SetupCommand extends Command
{
    public function name(): string
    {
        return 'setup';
    }

    public function description(): string
    {
        return 'Configura o ambiente interativamente (env, DB, app key)';
    }

    public function handle(array $args): int
    {
        $basePath = defined('VELO_BASE_PATH') ? VELO_BASE_PATH : getcwd();

        $this->info('⚡ VeloPHP Setup');
        $this->line('---');
        $this->line('');

        // ─── .env ────────────────────────────────────────────────────────

        $envPath = $basePath . '/.env';
        $envExamplePath = $basePath . '/.env.example';

        if (!file_exists($envPath) && file_exists($envExamplePath)) {
            if ($this->confirm('Arquivo .env nao encontrado. Criar a partir do .env.example?')) {
                copy($envExamplePath, $envPath);
                $this->info('.env criado.');
            }
        }

        if (!file_exists($envPath)) {
            $this->error('.env nao encontrado. Crie manualmente.');

            return 1;
        }

        // ─── Database ────────────────────────────────────────────────────

        if ($this->confirm('Configurar banco de dados?')) {
            $this->updateEnvVar($envPath, 'DB_HOST', $this->ask('DB_HOST', '127.0.0.1'));
            $this->updateEnvVar($envPath, 'DB_PORT', $this->ask('DB_PORT', '3306'));
            $this->updateEnvVar($envPath, 'DB_DATABASE', $this->ask('DB_DATABASE', 'velophp'));
            $this->updateEnvVar($envPath, 'DB_USERNAME', $this->ask('DB_USERNAME', 'root'));
            $this->updateEnvVar($envPath, 'DB_PASSWORD', $this->ask('DB_PASSWORD', ''));

            $this->info('Configuracao de banco salva.');
        }

        // ─── APP_KEY ────────────────────────────────────────────────────

        $envContent = file_get_contents($envPath);

        if (!str_contains($envContent, 'APP_KEY=') || preg_match('/^APP_KEY=$/m', $envContent)) {
            $key = bin2hex(random_bytes(32));

            if (str_contains($envContent, 'APP_KEY=')) {
                $envContent = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $envContent);
            } else {
                $envContent .= PHP_EOL . "APP_KEY={$key}" . PHP_EOL;
            }

            file_put_contents($envPath, $envContent);
            $this->info('APP_KEY gerada.');
        }

        // ─── Test DB ─────────────────────────────────────────────────────

        if ($this->confirm('Testar conexao com o banco?')) {
            $this->line('');
            $this->info('Testando conexao...');
            $this->line('');

            try {
                $app = new \Core\App(basePath: $basePath);
                $conn = $app->container()->make(\Core\Database\Connection::class);
                $conn->getPdo();
                $this->info('Conexao OK!');
            } catch (\Throwable $e) {
                $this->warn('Conexao falhou: ' . $e->getMessage());
                $this->warn('Configure manualmente em .env e tente novamente.');
            }
        }

        // ─── Done ────────────────────────────────────────────────────────

        $this->line('');
        $this->info('Setup concluido!');
        $this->line('');
        $this->line('Proximos passos:');
        $this->line('  php velo serve');
        $this->line('  php velo migrate');
        $this->line('');

        return 0;
    }

    private function ask(string $question, string $default = ''): string
    {
        echo "  {$question} [{$default}]: ";
        $input = trim((string) fgets(STDIN));

        return $input !== '' ? $input : $default;
    }

    private function confirm(string $question): bool
    {
        echo "  {$question} [Y/n]: ";
        $input = strtolower(trim((string) fgets(STDIN)));

        if ($input === '') {
            return true;
        }

        return in_array($input, ['y', 'yes', 's', 'sim'], true);
    }

    private function updateEnvVar(string $envPath, string $key, string $value): void
    {
        $content = file_get_contents($envPath);

        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $content);
        } else {
            $content .= PHP_EOL . "{$key}={$value}" . PHP_EOL;
        }

        file_put_contents($envPath, $content);
    }
}
