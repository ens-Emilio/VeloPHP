<?php

declare(strict_types=1);

namespace Core\Database;

abstract class Repository implements RepositoryInterface
{
    protected string $table;
    protected string $primaryKey = 'id';
    private ?int $lastLoadedId = null;
    private array $with = [];

    public function __construct(
        protected readonly Connection $connection,
    ) {
    }

    /**
     * Define relacionamentos para eager loading.
     */
    public function with(string ...$relations): static
    {
        $this->with = $relations;
        return $this;
    }

    public function first(): ?array
    {
        $result = $this->query()->first();
        if ($result !== null) {
            $this->lastLoadedId = (int) ($result[$this->primaryKey] ?? 0);
            $result = $this->resolveEagerRelations($result);
        }
        return $result;
    }

    public function get(): array
    {
        $results = $this->query()->get();
        return $this->resolveEagerRelationsForMany($results);
    }

    public function find(int|string $id): ?array
    {
        $result = $this->query()->where($this->primaryKey, $id)->first();
        if ($result !== null) {
            $this->lastLoadedId = (int) ($result[$this->primaryKey] ?? $id);
            $result = $this->resolveEagerRelations($result);
        }
        return $result;
    }

    public function all(): array
    {
        $results = $this->query()->get();
        return $this->resolveEagerRelationsForMany($results);
    }

    public function create(array $data): string
    {
        return $this->query()->insert($data);
    }

    public function update(int|string $id, array $data): bool
    {
        return $this->query()->where($this->primaryKey, $id)->update($data) > 0;
    }

    public function delete(int|string $id): bool
    {
        return $this->query()->where($this->primaryKey, $id)->delete() > 0;
    }

    /**
     * Relacionamento belongsTo (N:1).
     */
    protected function belongsTo(string $relatedTable, string $foreignKey, ?string $localKey = null, int|string|null $id = null): ?array
    {
        $localKey ??= $this->primaryKey;
        $id ??= $this->lastLoadedId;

        if ($id === null) {
            return null;
        }

        return $this->connection->table($relatedTable)
            ->where($localKey, $id)
            ->first();
    }

    /**
     * Relacionamento hasMany (1:N).
     */
    protected function hasMany(string $relatedTable, string $foreignKey, ?string $localKey = null, int|string|null $id = null): array
    {
        $localKey ??= $this->primaryKey;
        $id ??= $this->lastLoadedId;

        if ($id === null) {
            return [];
        }

        return $this->connection->table($relatedTable)
            ->where($foreignKey, $id)
            ->get();
    }

    /**
     * Relacionamento belongsToMany (N:N) via tabela pivô.
     */
    protected function belongsToMany(string $relatedTable, string $pivotTable, string $foreignPivotKey, string $relatedPivotKey, ?string $localKey = null, int|string|null $id = null): array
    {
        $localKey ??= $this->primaryKey;
        $id ??= $this->lastLoadedId;

        if ($id === null) {
            return [];
        }

        return $this->connection->select(
            "SELECT r.* FROM {$relatedTable} r
             INNER JOIN {$pivotTable} p ON p.{$relatedPivotKey} = r.{$localKey}
             WHERE p.{$foreignPivotKey} = ?",
            [$id]
        );
    }

    public function query(): QueryBuilder
    {
        return $this->connection->table($this->table);
    }

    public function findBy(string $column, mixed $value): ?array
    {
        return $this->query()->where($column, $value)->first();
    }

    public function findAllBy(string $column, mixed $value): array
    {
        return $this->query()->where($column, $value)->get();
    }

    public function exists(int|string $id): bool
    {
        return $this->query()->where($this->primaryKey, $id)->exists();
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $total = $this->count();
        $offset = ($page - 1) * $perPage;

        $items = $this->query()->limit($perPage)->offset($offset)->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    private function resolveEagerRelations(array $result): array
    {
        if (empty($this->with)) {
            return $result;
        }

        $result['_relations'] = [];

        foreach ($this->with as $relation) {
            if (!method_exists($this, $relation)) {
                throw new \RuntimeException("Relationship method '{$relation}' not found in " . static::class);
            }

            $result['_relations'][$relation] = $this->$relation();
        }

        return $result;
    }

    private function resolveEagerRelationsForMany(array $results): array
    {
        if (empty($this->with)) {
            return $results;
        }

        foreach ($results as $i => $result) {
            $this->lastLoadedId = (int) ($result[$this->primaryKey] ?? 0);
            $results[$i] = $this->resolveEagerRelations($result);
        }

        $this->with = [];
        return $results;
    }
}
