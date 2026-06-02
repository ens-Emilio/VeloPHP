<?php

declare(strict_types=1);

use Core\Database\Connection;
use Core\Database\QueryBuilder;

// Fake connection that only creates QueryBuilders (no real PDO)
class FakeJoinConnection extends Connection
{
    public function __construct()
    {
        parent::__construct('mysql', '', 0, '', '', '', 'utf8mb4');
    }
    public function getPdo(): \PDO
    {
        throw new \RuntimeException('Not needed');
    }
    public function raw(string $sql, array $bindings = []): \PDOStatement
    {
        throw new \RuntimeException('Not needed');
    }
}

test('query builder suporta alias de tabela', function () {
    $ref = new ReflectionMethod(QueryBuilder::class, 'buildSelect');

    $connection = new FakeJoinConnection();
    $qb = new QueryBuilder($connection, 'users AS u');
    $qb->select('u.id', 'u.name');

    $sql = $ref->invoke($qb);
    expect($sql)->toContain('FROM users AS u');
    expect($sql)->toContain('SELECT u.id, u.name');
});

test('query builder suporta join', function () {
    $ref = new ReflectionMethod(QueryBuilder::class, 'buildSelect');

    $connection = new FakeJoinConnection();
    $qb = new QueryBuilder($connection, 'users AS u');
    $qb->select('u.*', 'p.title');
    $qb->join('posts AS p', 'p.user_id', '=', 'u.id');

    $sql = $ref->invoke($qb);
    expect($sql)->toContain('INNER JOIN posts AS p ON p.user_id = u.id');
});

test('query builder suporta left join', function () {
    $ref = new ReflectionMethod(QueryBuilder::class, 'buildSelect');

    $connection = new FakeJoinConnection();
    $qb = new QueryBuilder($connection, 'users');
    $qb->leftJoin('posts', 'posts.user_id', '=', 'users.id');

    $sql = $ref->invoke($qb);
    expect($sql)->toContain('LEFT JOIN posts ON posts.user_id = users.id');
});

test('query builder suporta multiplos joins', function () {
    $ref = new ReflectionMethod(QueryBuilder::class, 'buildSelect');

    $connection = new FakeJoinConnection();
    $qb = new QueryBuilder($connection, 'users');
    $qb->join('posts', 'posts.user_id', '=', 'users.id')
       ->leftJoin('comments', 'comments.post_id', '=', 'posts.id');

    $sql = $ref->invoke($qb);
    expect($sql)->toContain('INNER JOIN posts');
    expect($sql)->toContain('LEFT JOIN comments');
});
