<?php

declare(strict_types=1);

namespace Core\Database;

class QueryBuilder
{
    private array $columns = ['*'];
    private array $wheres = [];
    private array $bindings = [];
    private ?string $orderBy = null;
    private string $orderDirection = 'ASC';
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
    private array $joins = [];
    private ?string $tableAlias = null;

    public function __construct(
        private readonly Connection $connection,
        private string $table,
    ) {
        if (preg_match('/^(\S+)\s+AS\s+(\S+)$/i', $table, $m)) {
            $this->table = $m[1];
            $this->tableAlias = $m[2];
        }
    }

    /**
     * Define as colunas a selecionar.
     */
    public function select(string ...$columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Adiciona condição WHERE.
     */
    public function where(string $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }

        $this->wheres[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * WHERE com IN.
     */
    public function whereIn(string $column, array $values): static
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * WHERE IS NULL.
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = "{$column} IS NULL";
        return $this;
    }

    /**
     * WHERE IS NOT NULL.
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = "{$column} IS NOT NULL";
        return $this;
    }

    /**
     * ORDER BY.
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBy = $column;
        $this->orderDirection = strtoupper($direction);
        return $this;
    }

    /**
     * LIMIT.
     */
    public function limit(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * OFFSET.
     */
    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Executa SELECT e retorna todos os resultados.
     */
    public function get(): array
    {
        $sql = $this->buildSelect();
        return $this->connection->select($sql, $this->bindings);
    }

    /**
     * Executa SELECT e retorna o primeiro resultado.
     */
    public function first(): ?array
    {
        $this->limitValue = 1;
        $sql = $this->buildSelect();
        return $this->connection->selectOne($sql, $this->bindings);
    }

    /**
     * Retorna o count.
     */
    public function count(): int
    {
        $original = $this->columns;
        $this->columns = ['COUNT(*) as aggregate'];
        $result = $this->first();
        $this->columns = $original;
        return (int) ($result['aggregate'] ?? 0);
    }

    /**
     * Verifica se existe pelo menos um registro.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * INSERT.
     */
    public function insert(array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->connection->raw($sql, array_values($data));

        return $this->connection->lastInsertId();
    }

    /**
     * INSERT múltiplo.
     */
    public function insertMany(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', $columns);
        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $placeholders = implode(', ', array_fill(0, count($rows), $rowPlaceholder));

        $bindings = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $bindings[] = $row[$col] ?? null;
            }
        }

        $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES {$placeholders}";
        return $this->connection->statement($sql, $bindings);
    }

    /**
     * UPDATE com as condições WHERE atuais.
     */
    public function update(array $data): int
    {
        $sets = implode(', ', array_map(fn ($col) => "{$col} = ?", array_keys($data)));
        $bindings = array_merge(array_values($data), $this->bindings);

        $sql = "UPDATE {$this->table} SET {$sets}";
        $sql .= $this->buildWhere();

        return $this->connection->statement($sql, $bindings);
    }

    /**
     * DELETE com as condições WHERE atuais.
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->buildWhere();

        return $this->connection->statement($sql, $this->bindings);
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    // --- SQL builders ---

    private function buildSelect(): string
    {
        $columns = implode(', ', $this->columns);
        $from = $this->table;
        if ($this->tableAlias !== null) {
            $from .= " AS {$this->tableAlias}";
        }

        $sql = "SELECT {$columns} FROM {$from}";
        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();

        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy} {$this->orderDirection}";
        }

        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }

        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }

        return $sql;
    }

    private function buildWhere(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $this->wheres);
    }

    private function buildJoins(): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = '';
        foreach ($this->joins as $join) {
            $type = $join['type'];
            $sql .= " {$type} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        return $sql;
    }
}
