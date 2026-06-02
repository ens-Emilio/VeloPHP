<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;
use PDOStatement;

class Connection
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $driver,
        private readonly string $host,
        private readonly int $port,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
        private readonly string $charset,
    ) {
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}";

            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return $this->pdo;
    }

    /**
     * Executa uma query raw com prepared statements.
     */
    public function raw(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /**
     * Executa SELECT e retorna todos os resultados.
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->raw($sql, $bindings)->fetchAll();
    }

    /**
     * Executa SELECT e retorna o primeiro resultado.
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $result = $this->raw($sql, $bindings)->fetch();
        return $result ?: null;
    }

    /**
     * Executa INSERT/UPDATE/DELETE e retorna o número de linhas afetadas.
     */
    public function statement(string $sql, array $bindings = []): int
    {
        return $this->raw($sql, $bindings)->rowCount();
    }

    /**
     * Retorna o último ID inserido.
     */
    public function lastInsertId(): string
    {
        return $this->getPdo()->lastInsertId();
    }

    /**
     * Executa uma transação.
     */
    public function transaction(callable $callback): mixed
    {
        $this->getPdo()->beginTransaction();

        try {
            $result = $callback($this);
            $this->getPdo()->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->getPdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Cria um QueryBuilder para a tabela.
     */
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /**
     * Testa a conexão com o banco.
     */
    public function test(): bool
    {
        try {
            $this->getPdo()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
