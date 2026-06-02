<?php

declare(strict_types=1);

namespace Core;

abstract class ServiceProvider
{
    public function __construct(
        protected App $app,
    ) {
    }

    /**
     * Registra bindings no container.
     */
    abstract public function register(): void;

    /**
     * Executado após todos os providers terem sido registrados.
     */
    public function boot(): void
    {
    }
}
