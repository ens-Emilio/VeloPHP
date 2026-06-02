<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

class Analyse extends Command
{
    public function name(): string
    {
        return 'analyse';
    }

    public function description(): string
    {
        return 'Executa análise estática com PHPStan';
    }

    public function handle(array $args): int
    {
        $binary = 'vendor/bin/phpstan';

        if (!file_exists($binary)) {
            $this->error('PHPStan não encontrado. Instale com: composer require --dev phpstan/phpstan');
            return 1;
        }

        $this->info('Executando PHPStan...');
        $this->line();

        $exitCode = 0;
        passthru("{$binary} analyse " . implode(' ', $args), $exitCode);
        return $exitCode;
    }
}
