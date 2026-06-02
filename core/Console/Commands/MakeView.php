<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Application;
use Core\Console\Command;

class MakeView extends Command
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function name(): string
    {
        return 'make:view';
    }

    public function description(): string
    {
        return 'Cria um novo template de view';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error('Uso: php velo make:view <nome> (ex: users/index)');
            return 1;
        }

        $path = $this->console->basePath() . '/resources/views/' . $name . '.velo.php';

        if (file_exists($path)) {
            $this->error("View [{$name}] ja existe!");
            return 1;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = <<<'PHP'
        <?php $__engine->extends('layouts/main'); ?>

        <?php $__engine->section('content'); ?>

        <h2>Nova pagina</h2>
        <p>Conteudo aqui.</p>

        <?php $__engine->endSection(); ?>
        PHP;

        file_put_contents($path, $content);
        $this->info("View criada: resources/views/{$name}.velo.php");

        return 0;
    }
}
