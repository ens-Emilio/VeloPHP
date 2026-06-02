<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

class Test extends Command
{
    public function name(): string
    {
        return 'test';
    }

    public function description(): string
    {
        return 'Executa testes com Pest';
    }

    public function handle(array $args): int
    {
        $binary = 'vendor/bin/pest';

        if (!file_exists($binary)) {
            $this->error('Pest não encontrado. Instale com: composer require --dev pestphp/pest');
            return 1;
        }

        $exitCode = 0;
        passthru("{$binary} " . implode(' ', $args), $exitCode);
        return $exitCode;
    }
}
