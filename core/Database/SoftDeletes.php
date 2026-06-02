<?php

declare(strict_types=1);

namespace Core\Database;

trait SoftDeletes
{
    public function query(): QueryBuilder
    {
        return $this->newBaseQuery()->whereNull('deleted_at');
    }

    public function delete(int|string $id): bool
    {
        return $this->query()
            ->where($this->primaryKey, $id)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function forceDelete(int|string $id): bool
    {
        return $this->newBaseQuery()
            ->where($this->primaryKey, $id)
            ->delete() > 0;
    }

    public function withTrashed(): QueryBuilder
    {
        return $this->newBaseQuery();
    }

    public function onlyTrashed(): QueryBuilder
    {
        return $this->newBaseQuery()->whereNotNull('deleted_at');
    }

    public function restore(int|string $id): bool
    {
        return $this->newBaseQuery()
            ->where($this->primaryKey, $id)
            ->update(['deleted_at' => null]) > 0;
    }

    protected function newBaseQuery(): QueryBuilder
    {
        return $this->connection->table($this->table);
    }
}
