<?php

declare(strict_types=1);

namespace Core;

class Session
{
    private bool $started = false;

    public function __construct(
        private readonly array $options = [],
    ) {
    }

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        $lifetime = $this->options['lifetime'] ?? 120;

        session_set_cookie_params([
            'lifetime' => $lifetime * 60,
            'path' => $this->options['path'] ?? '/',
            'secure' => $this->options['secure'] ?? false,
            'httponly' => $this->options['httponly'] ?? true,
            'samesite' => $this->options['samesite'] ?? 'Lax',
        ]);

        session_start();
        $this->started = true;

        // Processa flash data: move _flash.new → _flash.old, limpa old
        $this->ageFlashData();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION;
    }

    /**
     * Flash data — disponível apenas no próximo request.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
        $_SESSION['_flash']['new'][$key] = true;
    }

    /**
     * Destrói a sessão completamente.
     */
    public function destroy(): void
    {
        $this->ensureStarted();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        session_destroy();
        $this->started = false;
    }

    /**
     * Regenera o ID da sessão (segurança, ex: após login).
     */
    public function regenerate(): void
    {
        $this->ensureStarted();
        session_regenerate_id(true);
    }

    public function id(): string
    {
        $this->ensureStarted();
        return session_id();
    }

    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }

    private function ageFlashData(): void
    {
        // Remove dados flash do request anterior
        $old = $_SESSION['_flash']['old'] ?? [];
        foreach (array_keys($old) as $key) {
            unset($_SESSION[$key]);
        }

        // Move new → old para o próximo ciclo
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'] ?? [];
        $_SESSION['_flash']['new'] = [];
    }
}
