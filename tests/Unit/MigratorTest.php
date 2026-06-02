<?php

declare(strict_types=1);

use Core\Database\Connection;
use Core\Database\Migrator;

class MigratorFakeConnection extends Connection
{
    public function __construct()
    {
        parent::__construct('mysql', '', 0, '', '', '', 'utf8mb4');
    }

    public function getPdo(): \PDO
    {
        throw new \RuntimeException('Not needed');
    }

    public function selectOne(string $sql, array $bindings = []): ?array
    {
        // ensureMigrationsTable() checks if migrations table exists
        return ['table_name' => 'migrations'];
    }

    public function select(string $sql, array $bindings = []): array
    {
        return [];
    }

    public function raw(string $sql, array $bindings = []): \PDOStatement
    {
        throw new \RuntimeException('Not needed');
    }
}

test('migrator path retorna diretorio configurado', function () {
    $connection = new MigratorFakeConnection();
    $migrator = new Migrator($connection, '/tmp/migrations');

    expect($migrator->path())->toBe('/tmp/migrations');
});

test('migrator path remove trailing slash', function () {
    $connection = new MigratorFakeConnection();
    $migrator = new Migrator($connection, '/tmp/migrations/');

    expect($migrator->path())->toBe('/tmp/migrations');
});

test('migrator getPending retorna vazio quando diretorio vazio', function () {
    $dir = '/tmp/velophp_test_migrations_' . uniqid();
    mkdir($dir, 0777, true);

    $connection = new MigratorFakeConnection();
    $migrator = new Migrator($connection, $dir);

    expect($migrator->getPending())->toBe([]);

    rmdir($dir);
});

test('migrator getPending retorna arquivos .php quando diretorio tem migrations', function () {
    $dir = '/tmp/velophp_test_migrations_' . uniqid();
    mkdir($dir, 0777, true);
    touch($dir . '/2025_01_01_000001_test_migration.php');

    $connection = new MigratorFakeConnection();
    $migrator = new Migrator($connection, $dir);

    $pending = $migrator->getPending();
    expect($pending)->toHaveCount(1);
    expect($pending[0])->toBe('2025_01_01_000001_test_migration.php');

    unlink($dir . '/2025_01_01_000001_test_migration.php');
    rmdir($dir);
});
