<?php

declare(strict_types=1);

namespace Core\Database;

interface RepositoryInterface
{
    public function find(int|string $id): ?array;

    public function all(): array;

    public function create(array $data): string;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;

    public function query(): QueryBuilder;

    public function first(): ?array;

    public function get(): array;

    public function findBy(string $column, mixed $value): ?array;

    public function findAllBy(string $column, mixed $value): array;

    public function exists(int|string $id): bool;

    public function count(): int;

    public function paginate(int $page = 1, int $perPage = 15): array;

    public function with(string ...$relations): static;
}
