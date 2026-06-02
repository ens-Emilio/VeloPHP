<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

class Lint extends Command
{
    public function name(): string
    {
        return 'lint';
    }

    public function description(): string
    {
        return 'Verifica/corrige estilo de código com Pint';
    }

    public function handle(array $args): int
    {
        $binary = 'vendor/bin/pint';

        if (!file_exists($binary)) {
            $this->error('Pint não encontrado. Instale com: composer require --dev laravel/pint');
            return 1;
        }

        $exitCode = 0;
        passthru("{$binary} " . implode(' ', $args), $exitCode);
        return $exitCode;
    }
}
