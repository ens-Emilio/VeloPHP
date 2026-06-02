<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;
use Core\Database\Connection;

class DbTest extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'db:test';
    }

    public function description(): string
    {
        return 'Testa a conexao com o banco de dados';
    }

    public function handle(array $args): int
    {
        $basePath = $this->console->basePath();

        try {
            $app = new \Core\App(basePath: $basePath);

            /** @var Connection $connection */
            $connection = $app->container()->make(Connection::class);

            if ($connection->test()) {
                $this->info('Conexao com o banco de dados estabelecida com sucesso!');

                $config = $app->config();
                $this->line("  Driver:   {$config->get('database.driver')}");
                $this->line("  Host:     {$config->get('database.host')}:{$config->get('database.port')}");
                $this->line("  Database: {$config->get('database.database')}");

                return 0;
            }

            $this->error('Falha ao conectar com o banco de dados.');
            return 1;
        } catch (\Throwable $e) {
            $this->error('Erro: ' . $e->getMessage());
            return 1;
        }
    }
}
