<?php

declare(strict_types=1);

namespace Core\Database\Schema;

class ColumnDefinition
{
    private array $attributes = [];

    public function __construct(
        private readonly string $type,
        private readonly string $name,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function getName(): string
    {
        return $this->name;
    }

    public function nullable(bool $nullable = true): static
    {
        $this->attributes['nullable'] = $nullable;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->attributes['default'] = $value;
        return $this;
    }

    public function unique(): static
    {
        $this->attributes['unique'] = true;
        return $this;
    }

    public function primary(): static
    {
        $this->attributes['primary'] = true;
        return $this;
    }

    public function autoIncrement(): static
    {
        $this->attributes['autoIncrement'] = true;
        return $this;
    }

    public function unsigned(): static
    {
        $this->attributes['unsigned'] = true;
        return $this;
    }

    public function after(string $column): static
    {
        $this->attributes['after'] = $column;
        return $this;
    }

    public function length(int $length): static
    {
        $this->attributes['length'] = $length;
        return $this;
    }

    public function precision(int $total, int $places): static
    {
        $this->attributes['precision'] = [$total, $places];
        return $this;
    }

    public function values(array $values): static
    {
        $this->attributes['values'] = $values;
        return $this;
    }

    public function constrained(?string $table = null, ?string $reference = 'id'): static
    {
        $this->attributes['foreign'] = [
            'table' => $table ?? $this->inferForeignTable(),
            'reference' => $reference,
        ];
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    private function inferForeignTable(): string
    {
        $name = $this->name;
        if (str_ends_with($name, '_id')) {
            return substr($name, 0, -3);
        }
        return $name . 's';
    }
}
