<?php

declare(strict_types=1);

namespace Core\Console;

abstract class Command
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function handle(array $args): int;

    // --- Output helpers ---

    protected function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m" . PHP_EOL;
    }

    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m" . PHP_EOL;
    }

    protected function warn(string $message): void
    {
        echo "\033[33m{$message}\033[0m" . PHP_EOL;
    }

    protected function line(string $message = ''): void
    {
        echo $message . PHP_EOL;
    }

    protected function table(array $headers, array $rows): void
    {
        // Calcula largura de cada coluna
        $widths = array_map('strlen', $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, strlen((string) $cell));
            }
        }

        // Linha separadora
        $separator = '+' . implode('+', array_map(fn ($w) => str_repeat('-', $w + 2), $widths)) . '+';

        $this->line($separator);

        // Headers
        $line = '|';
        foreach ($headers as $i => $header) {
            $line .= ' ' . str_pad($header, $widths[$i]) . ' |';
        }
        $this->line("\033[1m{$line}\033[0m");
        $this->line($separator);

        // Rows
        foreach ($rows as $row) {
            $line = '|';
            foreach ($row as $i => $cell) {
                $line .= ' ' . str_pad((string) $cell, $widths[$i]) . ' |';
            }
            $this->line($line);
        }

        $this->line($separator);
    }
}
