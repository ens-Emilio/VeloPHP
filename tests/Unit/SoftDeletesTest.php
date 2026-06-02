<?php

declare(strict_types=1);

use Core\Database\Connection;
use Core\Database\QueryBuilder;
use Core\Database\Repository;
use Core\Database\SoftDeletes;

// Concrete repository using the trait
class SoftDeleteTestRepository extends Repository
{
    use SoftDeletes;

    protected string $table = 'users';
    protected string $primaryKey = 'id';
}

// Fake connection for soft delete tests
class SoftDeleteFakeConnection extends Connection
{
    public function __construct()
    {
        parent::__construct('mysql', '', 0, '', '', '', 'utf8mb4');
    }
    public function getPdo(): \PDO
    {
        throw new \RuntimeException('Not needed');
    }
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }
}

test('soft deletes trait adiciona whereNull deleted_at na query padrao', function () {
    $connection = new SoftDeleteFakeConnection();

    $repo = new SoftDeleteTestRepository($connection);
    $qb = $repo->query();

    $ref = new ReflectionMethod(QueryBuilder::class, 'buildSelect');
    $ref->setAccessible(true);

    $sql = $ref->invoke($qb);
    expect($sql)->toContain('WHERE deleted_at IS NULL');
});

test('soft deletes withTrashed retorna query sem filtro', function () {
    $connection = new SoftDeleteFakeConnection();

    $repo = new SoftDeleteTestRepository($connection);
    $qb = $repo->withTrashed();

    $ref = new ReflectionMethod(QueryBuilder::class, 'buildSelect');
    $ref->setAccessible(true);

    $sql = $ref->invoke($qb);
    expect($sql)->not->toContain('deleted_at');
});

test('soft deletes onlyTrashed retorna query com whereNotNull', function () {
    $connection = new SoftDeleteFakeConnection();

    $repo = new SoftDeleteTestRepository($connection);
    $qb = $repo->onlyTrashed();

    $ref = new ReflectionMethod(QueryBuilder::class, 'buildSelect');
    $ref->setAccessible(true);

    $sql = $ref->invoke($qb);
    expect($sql)->toContain('deleted_at IS NOT NULL');
});
