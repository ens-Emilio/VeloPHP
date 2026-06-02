<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Database\Schema\Schema;

class Migrator
{
    private string $path;
    private Connection $connection;

    public function __construct(Connection $connection, string $path)
    {
        $this->connection = $connection;
        $this->path = rtrim($path, '/');
    }

    public function path(): string
    {
        return $this->path;
    }

    public function run(): array
    {
        $this->ensureMigrationsTable();

        if (!$this->acquireLock()) {
            throw new \RuntimeException('Cannot acquire migration lock. Another migration may be running.');
        }

        try {
            $ran = $this->getRan();
            $files = $this->getMigrationFiles();
            $pending = array_diff($files, $ran);
            sort($pending);

            $batch = $this->getNextBatchNumber();
            $executed = [];

            foreach ($pending as $file) {
                $migration = $this->resolve($file);
                $migration->up();
                $this->log($file, $batch);
                $executed[] = $file;
            }

            return $executed;
        } finally {
            $this->releaseLock();
        }
    }

    public function rollback(): array
    {
        $this->ensureMigrationsTable();

        if (!$this->acquireLock()) {
            throw new \RuntimeException('Cannot acquire migration lock.');
        }

        try {
            $lastBatch = $this->getLastBatchNumber();
            $migrations = $this->getMigrationsByBatch($lastBatch);

            $rolledBack = [];

            foreach ($migrations as $migration) {
                $instance = $this->resolve($migration['migration']);
                $instance->down();
                $this->removeLog($migration['migration']);
                $rolledBack[] = $migration['migration'];
            }

            return $rolledBack;
        } finally {
            $this->releaseLock();
        }
    }

    public function reset(): array
    {
        $this->ensureMigrationsTable();

        if (!$this->acquireLock()) {
            throw new \RuntimeException('Cannot acquire migration lock.');
        }

        try {
            $all = $this->getAllMigrations();
            $rolledBack = [];

            foreach ($all as $migration) {
                $instance = $this->resolve($migration['migration']);
                $instance->down();
                $this->removeLog($migration['migration']);
                $rolledBack[] = $migration['migration'];
            }

            return $rolledBack;
        } finally {
            $this->releaseLock();
        }
    }

    public function fresh(): void
    {
        $tables = $this->connection->select(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()'
        );

        foreach ($tables as $row) {
            $name = $row['table_name'];
            if ($name === 'migrations') {
                continue;
            }
            Schema::dropIfExists($name);
        }

        $this->connection->raw('DELETE FROM migrations');
        $this->run();
    }

    /** @return string[] */
    public function getPending(): array
    {
        $this->ensureMigrationsTable();
        $ran = $this->getRan();
        $files = $this->getMigrationFiles();
        $pending = array_diff($files, $ran);
        sort($pending);
        return $pending;
    }

    // --- Internal ---

    private function ensureMigrationsTable(): void
    {
        $result = $this->connection->selectOne(
            "SELECT * FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'migrations'"
        );

        if ($result === null) {
            $this->connection->raw(
                "CREATE TABLE migrations (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INT UNSIGNED NOT NULL,
                    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
            );
        }
    }

    private function acquireLock(): bool
    {
        $result = $this->connection->selectOne("SELECT GET_LOCK('velophp_migrations', 10) AS locked");
        return ($result['locked'] ?? 0) == 1;
    }

    private function releaseLock(): void
    {
        $this->connection->raw("SELECT RELEASE_LOCK('velophp_migrations')");
    }

    /** @return string[] */
    private function getRan(): array
    {
        $rows = $this->connection->select('SELECT migration FROM migrations ORDER BY migration');
        return array_map(fn ($r) => $r['migration'], $rows);
    }

    /** @return string[] */
    private function getMigrationFiles(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $files = scandir($this->path);
        $files = array_filter($files, fn ($f) => str_ends_with($f, '.php'));

        return array_values($files);
    }

    private function resolve(string $file): \Core\Database\Schema\Migration
    {
        $path = $this->path . '/' . $file;
        $class = require $path;

        if (is_object($class) && $class instanceof \Core\Database\Schema\Migration) {
            return $class;
        }

        $className = $this->getClassName($file);
        if (class_exists($className)) {
            return new $className();
        }

        throw new \RuntimeException("Migration {$file} did not return a Migration instance.");
    }

    private function getClassName(string $file): string
    {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $parts = explode('_', $name);
        $parts = array_slice($parts, 4);

        $className = '';
        foreach ($parts as $part) {
            if ($part === 'table') {
                continue;
            }
            $className .= ucfirst($part);
        }

        return $className . 'Table';
    }

    private function log(string $file, int $batch): void
    {
        $this->connection->raw(
            'INSERT INTO migrations (migration, batch) VALUES (?, ?)',
            [$file, $batch]
        );
    }

    private function removeLog(string $file): void
    {
        $this->connection->raw(
            'DELETE FROM migrations WHERE migration = ?',
            [$file]
        );
    }

    private function getNextBatchNumber(): int
    {
        $result = $this->connection->selectOne('SELECT COALESCE(MAX(batch), 0) + 1 AS next FROM migrations');
        return (int) ($result['next'] ?? 1);
    }

    private function getLastBatchNumber(): int
    {
        $result = $this->connection->selectOne('SELECT MAX(batch) AS last FROM migrations');
        return (int) ($result['last'] ?? 0);
    }

    private function getMigrationsByBatch(int $batch): array
    {
        return $this->connection->select(
            'SELECT * FROM migrations WHERE batch = ? ORDER BY migration DESC',
            [$batch]
        );
    }

    private function getAllMigrations(): array
    {
        return $this->connection->select('SELECT * FROM migrations ORDER BY batch DESC, migration DESC');
    }
}
