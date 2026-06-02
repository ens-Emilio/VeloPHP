<?php

declare(strict_types=1);

namespace Core\Database\Schema;

class Grammar
{
    public function compileCreate(Blueprint $blueprint): string
    {
        $table = $blueprint->getTable();
        $columns = $this->compileColumns($blueprint);
        $indices = $this->compileIndices($blueprint);

        $sql = "CREATE TABLE {$table} (\n  " . implode(",\n  ", array_merge($columns, $indices)) . "\n)";

        return $sql;
    }

    public function compileAlter(Blueprint $blueprint): array
    {
        $table = $blueprint->getTable();
        $statements = [];

        $added = $this->compileAddedColumns($blueprint);
        if ($added) {
            $statements[] = "ALTER TABLE {$table} " . implode(", ", $added);
        }

        $modified = $this->compileModifiedColumns($blueprint);
        if ($modified) {
            $statements[] = "ALTER TABLE {$table} " . implode(", ", $modified);
        }

        foreach ($blueprint->getCommands() as $command) {
            $statements[] = match ($command['type']) {
                'dropColumn' => "ALTER TABLE {$table} DROP COLUMN " . implode(', DROP COLUMN ', $command['columns']),
                'dropPrimary' => "ALTER TABLE {$table} DROP PRIMARY KEY",
                'dropUnique' => "ALTER TABLE {$table} DROP INDEX {$command['index']}",
                'dropIndex' => "ALTER TABLE {$table} DROP INDEX {$command['index']}",
                'dropForeign' => "ALTER TABLE {$table} DROP FOREIGN KEY {$command['index']}",
                default => throw new \RuntimeException("Unknown command: {$command['type']}"),
            };
        }

        return $statements;
    }

    public function compileDrop(string $table): string
    {
        return "DROP TABLE {$table}";
    }

    public function compileDropIfExists(string $table): string
    {
        return "DROP TABLE IF EXISTS {$table}";
    }

    public function compileTableExists(): string
    {
        return 'SELECT * FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?';
    }

    /** @return string[] */
    private function compileColumns(Blueprint $blueprint): array
    {
        $lines = [];
        foreach ($blueprint->getColumns() as $column) {
            $lines[] = $this->compileColumnDefinition($column);
        }
        return $lines;
    }

    /** @return string[] */
    private function compileIndices(Blueprint $blueprint): array
    {
        $lines = [];
        foreach ($blueprint->getColumns() as $column) {
            $attrs = $column->getAttributes();

            if (!empty($attrs['primary'])) {
                $lines[] = "PRIMARY KEY ({$column->getName()})";
            }
            if (!empty($attrs['unique'])) {
                $lines[] = "UNIQUE INDEX {$column->getName()}_unique ({$column->getName()})";
            }
            if (!empty($attrs['foreign'])) {
                $fk = $attrs['foreign'];
                $lines[] = "FOREIGN KEY ({$column->getName()}) REFERENCES {$fk['table']}({$fk['reference']})";
            }
        }
        return $lines;
    }

    /** @return string[] */
    private function compileAddedColumns(Blueprint $blueprint): array
    {
        $parts = [];
        foreach ($blueprint->getColumns() as $column) {
            $parts[] = 'ADD ' . $this->compileColumnDefinition($column);
        }

        foreach ($blueprint->getColumns() as $column) {
            $attrs = $column->getAttributes();
            if (!empty($attrs['unique'])) {
                $parts[] = "ADD UNIQUE INDEX {$column->getName()}_unique ({$column->getName()})";
            }
            if (!empty($attrs['foreign'])) {
                $fk = $attrs['foreign'];
                $parts[] = "ADD FOREIGN KEY ({$column->getName()}) REFERENCES {$fk['table']}({$fk['reference']})";
            }
        }

        return $parts;
    }

    /** @return string[] */
    private function compileModifiedColumns(Blueprint $blueprint): array
    {
        return [];
    }

    private function compileColumnDefinition(ColumnDefinition $column): string
    {
        $type = $column->getType();
        $name = $column->getName();
        $attrs = $column->getAttributes();

        $typeMap = [
            'varchar' => 'VARCHAR',
            'int' => 'INT',
            'bigint' => 'BIGINT',
            'text' => 'TEXT',
            'tinyint' => 'TINYINT',
            'date' => 'DATE',
            'datetime' => 'DATETIME',
            'float' => 'FLOAT',
            'decimal' => 'DECIMAL',
            'enum' => 'ENUM',
            'char' => 'CHAR',
        ];

        $sqlType = $typeMap[$type] ?? strtoupper($type);

        $sql = "{$name} {$sqlType}";

        if (isset($attrs['length']) && in_array($type, ['varchar', 'int', 'bigint', 'char', 'tinyint'])) {
            $sql .= "({$attrs['length']})";
        }

        if (isset($attrs['precision'])) {
            $sql .= "({$attrs['precision'][0]}, {$attrs['precision'][1]})";
        }

        if (isset($attrs['values'])) {
            $escaped = array_map(fn ($v) => "'{$v}'", $attrs['values']);
            $sql .= '(' . implode(', ', $escaped) . ')';
        }

        if (!empty($attrs['unsigned'])) {
            $sql .= ' UNSIGNED';
        }

        if (!empty($attrs['autoIncrement'])) {
            $sql .= ' AUTO_INCREMENT';
        }

        if (isset($attrs['default'])) {
            $default = $attrs['default'];
            if (in_array($default, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'NULL'])) {
                $sql .= " DEFAULT {$default}";
            } else {
                $sql .= " DEFAULT '" . addslashes((string) $default) . "'";
            }
        } else {
            $sql .= ' NOT NULL';
        }

        if (!empty($attrs['nullable'])) {
            $sql = str_replace(' NOT NULL', '', $sql);
            if (!isset($attrs['default'])) {
                $sql .= ' DEFAULT NULL';
            }
        }

        if (!empty($attrs['primary']) && empty($attrs['autoIncrement'])) {
            // PRIMARY KEY is added as index constraint for non-autoIncrement
        }

        if (isset($attrs['after'])) {
            $sql .= " AFTER {$attrs['after']}";
        }

        return $sql;
    }
}
