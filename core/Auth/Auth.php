<?php

declare(strict_types=1);

namespace Core\Auth;

use Core\Database\Connection;
use Core\Session;

class Auth
{
    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(
        private readonly Session $session,
        private readonly Connection $connection,
        private readonly string $table = 'users',
        private readonly string $primaryKey = 'id',
        private readonly string $passwordColumn = 'password',
    ) {
    }

    /**
     * Autentica o usuário por credenciais (email + senha).
     */
    public function attempt(string $email, string $password): bool
    {
        $user = $this->connection->table($this->table)
            ->where('email', $email)
            ->first();

        if ($user === null) {
            return false;
        }

        if (!self::verify($password, $user[$this->passwordColumn])) {
            return false;
        }

        $this->login($user);
        return true;
    }

    /**
     * Loga um usuário (armazena na sessão).
     */
    public function login(array $user): void
    {
        $this->session->start();
        $this->session->regenerate();
        $this->session->set('_auth_user_id', $user[$this->primaryKey]);
        $this->user = $user;
        $this->loaded = true;
    }

    /**
     * Desloga o usuário.
     */
    public function logout(): void
    {
        $this->session->start();
        $this->session->remove('_auth_user_id');
        $this->session->regenerate();
        $this->user = null;
        $this->loaded = true;
    }

    /**
     * Verifica se há um usuário autenticado.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Verifica se NÃO há um usuário autenticado.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Retorna o usuário autenticado ou null.
     */
    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->user;
        }

        $this->loaded = true;
        $this->session->start();

        $userId = $this->session->get('_auth_user_id');

        if ($userId === null) {
            return null;
        }

        $this->user = $this->connection->table($this->table)
            ->where($this->primaryKey, $userId)
            ->first();

        // Se o usuário não existe mais no banco, limpa sessão
        if ($this->user === null) {
            $this->session->remove('_auth_user_id');
        }

        return $this->user;
    }

    /**
     * Retorna o ID do usuário autenticado.
     */
    public function id(): mixed
    {
        return $this->user()[$this->primaryKey] ?? null;
    }

    /**
     * Gera hash de senha com bcrypt.
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verifica senha contra hash.
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
