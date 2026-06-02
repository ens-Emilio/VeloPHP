<?php

declare(strict_types=1);

use Core\Database\Connection;
use Core\Database\QueryBuilder;
use Core\Database\Repository;

class RelationPostRepository extends Repository
{
    protected string $table = 'posts';
    protected string $primaryKey = 'id';

    public function author(): ?array
    {
        return $this->belongsTo('users', 'user_id');
    }

    public function comments(): array
    {
        return $this->hasMany('comments', 'post_id');
    }
}

// Fake connection for relationship tests
class FakeRelationConnection extends Connection
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
    public function select(string $sql, array $bindings = []): array
    {
        if (str_contains($sql, 'users')) {
            return [['id' => 5, 'name' => 'John']];
        }
        if (str_contains($sql, 'comments')) {
            return [
                ['id' => 1, 'body' => 'Nice!'],
                ['id' => 2, 'body' => 'Cool!'],
            ];
        }
        return [];
    }
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        if (str_contains($sql, 'users')) {
            return ['id' => 5, 'name' => 'John'];
        }
        return null;
    }
}

test('belongsTo retorna autor do post', function () {
    $connection = new FakeRelationConnection();
    $repo = new RelationPostRepository($connection);

    // Set lastLoadedId via reflection
    $prop = new ReflectionProperty(Repository::class, 'lastLoadedId');
    $prop->setValue($repo, 5);

    $author = $repo->author();
    expect($author)->toBe(['id' => 5, 'name' => 'John']);
});

test('hasMany retorna comentarios do post', function () {
    $connection = new FakeRelationConnection();
    $repo = new RelationPostRepository($connection);

    $prop = new ReflectionProperty(Repository::class, 'lastLoadedId');
    $prop->setValue($repo, 1);

    $comments = $repo->comments();
    expect($comments)->toHaveCount(2);
    expect($comments[0]['body'])->toBe('Nice!');
    expect($comments[1]['body'])->toBe('Cool!');
});
