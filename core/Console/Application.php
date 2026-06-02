<?php

declare(strict_types=1);

namespace Core\Console;

class Application
{
    /** @var Command[] */
    private array $commands = [];

    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function register(Command $command): void
    {
        $this->commands[$command->name()] = $command;
    }

    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? null;
        $args = array_slice($argv, 2);

        if ($commandName === null || $commandName === 'list' || $commandName === 'help') {
            return $this->showHelp();
        }

        $command = $this->commands[$commandName] ?? null;

        if ($command === null) {
            echo "\033[31mComando '{$commandName}' não encontrado.\033[0m" . PHP_EOL;
            echo PHP_EOL;
            return $this->showHelp();
        }

        return $command->handle($args);
    }

    private function showHelp(): int
    {
        echo "\033[1;36m" . PHP_EOL;
        echo " __     __   _       ____  _   _ ____  " . PHP_EOL;
        echo " \\ \\   / /__| | ___ |  _ \\| | | |  _ \\ " . PHP_EOL;
        echo "  \\ \\ / / _ \\ |/ _ \\| |_) | |_| | |_) |" . PHP_EOL;
        echo "   \\ V /  __/ | (_) |  __/|  _  |  __/ " . PHP_EOL;
        echo "    \\_/ \\___|_|\\___/|_|   |_| |_|_|    " . PHP_EOL;
        echo "\033[0m" . PHP_EOL;
        echo "\033[33mUso:\033[0m php velo <comando> [argumentos]" . PHP_EOL;
        echo PHP_EOL;
        echo "\033[33mComandos disponíveis:\033[0m" . PHP_EOL;

        $maxLen = 0;
        foreach ($this->commands as $name => $command) {
            $maxLen = max($maxLen, strlen($name));
        }

        foreach ($this->commands as $name => $command) {
            $padded = str_pad($name, $maxLen + 2);
            echo "  \033[32m{$padded}\033[0m{$command->description()}" . PHP_EOL;
        }

        echo PHP_EOL;
        return 0;
    }
}
