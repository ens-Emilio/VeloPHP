<?php

declare(strict_types=1);

namespace Core\View;

class Engine
{
    private string $viewPath;
    private string $extension;

    /** @var array<string, string> Sections capturadas */
    private array $sections = [];

    /** @var string|null Nome da section sendo capturada */
    private ?string $currentSection = null;

    /** @var string|null Layout a estender */
    private ?string $layout = null;

    public function __construct(string $viewPath, string $extension = '.velo.php')
    {
        $this->viewPath = rtrim($viewPath, '/');
        $this->extension = $extension;
    }

    /**
     * Renderiza um template e retorna o HTML.
     */
    public function render(string $view, array $data = []): string
    {
        $this->layout = null;
        $this->sections = [];

        $content = $this->renderView($view, $data);

        // Se o template declarou extends(), renderiza o layout
        $layout = $this->layout;
        if ($layout !== null) {
            return $this->renderView($layout, $data);
        }

        return $content;
    }

    /**
     * Renderiza um arquivo de view.
     *
     * @phpstan-impure
     */
    private function renderView(string $view, array $data): string
    {
        $file = $this->viewPath . '/' . str_replace('.', '/', $view) . $this->extension;

        if (!file_exists($file)) {
            throw new \RuntimeException("View [{$view}] não encontrada: {$file}");
        }

        // Disponibiliza $data como variáveis + engine como $__engine
        extract($data, EXTR_SKIP);
        $__engine = $this;

        ob_start();
        require $file;
        return ob_get_clean();
    }

    // --- Template directives (usadas dentro dos templates) ---

    /**
     * Define o layout pai. Uso: $__engine->extends('layouts/main')
     */
    public function extends(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Inicia captura de section. Uso: $__engine->section('content')
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * Finaliza captura da section. Uso: $__engine->endSection()
     */
    public function endSection(): void
    {
        if ($this->currentSection !== null) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    /**
     * Renderiza conteúdo de uma section. Uso: $__engine->yield('content')
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Inclui outro template. Uso: $__engine->include('partials/header')
     */
    public function include(string $view, array $data = []): string
    {
        return $this->renderView($view, $data);
    }
}
