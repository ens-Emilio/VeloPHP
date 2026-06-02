<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

class Serve extends Command
{
    public function name(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Inicia o servidor de desenvolvimento PHP';
    }

    public function handle(array $args): int
    {
        $host = $args[0] ?? '127.0.0.1';
        $port = $args[1] ?? '8000';

        $this->info("VeloPHP servidor de desenvolvimento iniciado: http://{$host}:{$port}");
        $this->line("Pressione Ctrl+C para parar.");
        $this->line();

        passthru("php -S {$host}:{$port} -t public");
        return 0;
    }
}
