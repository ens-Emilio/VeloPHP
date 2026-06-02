<?php

declare(strict_types=1);

namespace Core\Database;

/**
 * Facade estática para acesso ao banco de dados.
 *
 * Uso:
 *   DB::table('users')->where('id', 1)->first()
 *   DB::raw('SELECT * FROM users WHERE id = ?', [1])
 *   DB::connection()->transaction(fn($db) => ...)
 */
class DB
{
    /**
     * Cria um QueryBuilder para a tabela.
     */
    public static function table(string $table): QueryBuilder
    {
        return static::connection()->table($table);
    }

    /**
     * Executa uma query raw.
     */
    public static function raw(string $sql, array $bindings = []): \PDOStatement
    {
        return static::connection()->raw($sql, $bindings);
    }

    /**
     * Executa SELECT e retorna todos os resultados.
     */
    public static function select(string $sql, array $bindings = []): array
    {
        return static::connection()->select($sql, $bindings);
    }

    /**
     * Executa INSERT/UPDATE/DELETE e retorna linhas afetadas.
     */
    public static function statement(string $sql, array $bindings = []): int
    {
        return static::connection()->statement($sql, $bindings);
    }

    /**
     * Executa uma transação.
     */
    public static function transaction(callable $callback): mixed
    {
        return static::connection()->transaction($callback);
    }

    /**
     * Retorna a instância da conexão.
     */
    public static function connection(): Connection
    {
        return \app(Connection::class);
    }
}
