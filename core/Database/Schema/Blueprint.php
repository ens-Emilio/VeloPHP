<?php

declare(strict_types=1);

namespace Core\Database\Schema;

class Blueprint
{
    private string $table;
    private string $action;
    private array $columns = [];
    private array $commands = [];

    public function __construct(string $table, string $action = 'create')
    {
        $this->table = $table;
        $this->action = $action;
    }

    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->addColumn('bigint', $name)->unsigned()->autoIncrement()->primary();
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('varchar', $name)->length($length);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn('text', $name);
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn('int', $name);
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('bigint', $name);
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn('tinyint', $name)->length(1);
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn('date', $name);
    }

    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn('datetime', $name);
    }

    public function float(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn('float', $name)->precision($precision, $scale);
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn('decimal', $name)->precision($precision, $scale);
    }

    public function enum(string $name, array $values): ColumnDefinition
    {
        return $this->addColumn('enum', $name)->values($values);
    }

    public function uuid(string $name = 'uuid'): ColumnDefinition
    {
        return $this->addColumn('char', $name)->length(36);
    }

    public function foreignId(string $name): ColumnDefinition
    {
        return $this->addColumn('bigint', $name)->unsigned();
    }

    public function timestamps(?string $createdAt = 'created_at', ?string $updatedAt = 'updated_at'): void
    {
        if ($createdAt !== null) {
            $this->datetime($createdAt)->nullable()->default('CURRENT_TIMESTAMP');
        }
        if ($updatedAt !== null) {
            $this->datetime($updatedAt)->nullable()->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }

    public function softDeletes(string $name = 'deleted_at'): ColumnDefinition
    {
        return $this->datetime($name)->nullable();
    }

    public function dropColumn(string ...$columns): static
    {
        $this->commands[] = ['type' => 'dropColumn', 'columns' => $columns];
        return $this;
    }

    public function dropPrimary(string $index = 'primary'): static
    {
        $this->commands[] = ['type' => 'dropPrimary', 'index' => $index];
        return $this;
    }

    public function dropUnique(string $index): static
    {
        $this->commands[] = ['type' => 'dropUnique', 'index' => $index];
        return $this;
    }

    public function dropIndex(string $index): static
    {
        $this->commands[] = ['type' => 'dropIndex', 'index' => $index];
        return $this;
    }

    public function dropForeign(string $index): static
    {
        $this->commands[] = ['type' => 'dropForeign', 'index' => $index];
        return $this;
    }

    public function dropTimestamps(): static
    {
        $this->dropColumn('created_at', 'updated_at');
        return $this;
    }

    public function dropSoftDeletes(): static
    {
        return $this->dropColumn('deleted_at');
    }

    private function addColumn(string $type, string $name): ColumnDefinition
    {
        $column = new ColumnDefinition($type, $name);
        $this->columns[] = $column;
        return $column;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }
}
