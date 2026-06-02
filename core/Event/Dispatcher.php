<?php

declare(strict_types=1);

namespace Core\Event;

class Dispatcher
{
    /** @var array<string, callable[]> */
    private array $listeners = [];

    /**
     * Registra um listener para um evento.
     */
    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispara um evento, executando todos os listeners registrados.
     *
     * @return array Respostas dos listeners
     */
    public function dispatch(string $event, mixed $data = null): array
    {
        $responses = [];

        foreach ($this->listeners[$event] ?? [] as $listener) {
            $responses[] = $listener($data);
        }

        return $responses;
    }

    /**
     * Verifica se há listeners registrados para um evento.
     */
    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }

    /**
     * Remove todos os listeners de um evento.
     */
    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }
}
