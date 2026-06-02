<?php

declare(strict_types=1);

namespace Core\Database\Schema;

use Core\Database\Connection;

class Schema
{
    private static ?Connection $connection = null;
    private static ?Grammar $grammar = null;

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, 'create');
        $callback($blueprint);

        $grammar = self::grammar();
        $sql = $grammar->compileCreate($blueprint);

        self::connection()->raw($sql);
    }

    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, 'alter');
        $callback($blueprint);

        $grammar = self::grammar();
        $statements = $grammar->compileAlter($blueprint);

        foreach ($statements as $sql) {
            self::connection()->raw($sql);
        }
    }

    public static function drop(string $table): void
    {
        self::connection()->raw(self::grammar()->compileDrop($table));
    }

    public static function dropIfExists(string $table): void
    {
        self::connection()->raw(self::grammar()->compileDropIfExists($table));
    }

    public static function hasTable(string $table): bool
    {
        $result = self::connection()->selectOne(
            self::grammar()->compileTableExists(),
            [$table]
        );
        return $result !== null;
    }

    public static function pretend(callable $callback): array
    {
        $captured = new \ArrayObject();
        $real = self::$connection;

        // Intercepta raw() para capturar SQL em vez de executar
        $mock = new class ($captured) extends Connection {
            private \ArrayObject $captured;
            public function __construct(\ArrayObject $captured)
            {
                parent::__construct('', '', 0, '', '', '', '');
                $this->captured = $captured;
            }
            public function raw(string $sql, array $bindings = []): \PDOStatement
            {
                $this->captured->append($sql);
                throw new \RuntimeException('PRETEND_MODE');
            }
        };

        self::$connection = $mock;

        try {
            $callback();
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'PRETEND_MODE') {
                self::$connection = $real;
                throw $e;
            }
        }

        self::$connection = $real;

        return iterator_to_array($captured);
    }

    public static function connection(): Connection
    {
        if (self::$connection === null) {
            self::$connection = \app(\Core\Database\Connection::class);
        }
        return self::$connection;
    }

    private static function grammar(): Grammar
    {
        if (self::$grammar === null) {
            self::$grammar = new Grammar();
        }
        return self::$grammar;
    }
}
