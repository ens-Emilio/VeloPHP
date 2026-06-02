<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class CacheClear extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'cache:clear';
    }

    public function description(): string
    {
        return 'Limpa o cache da aplicação';
    }

    public function handle(array $args): int
    {
        $cachePath = $this->console->basePath() . '/storage/cache';

        if (!is_dir($cachePath)) {
            $this->warn('Diretório de cache não encontrado.');
            return 0;
        }

        $files = glob($cachePath . '/*.cache');
        $count = count($files);

        foreach ($files as $file) {
            unlink($file);
        }

        $this->info("Cache limpo! {$count} arquivo(s) removido(s).");

        return 0;
    }
}
