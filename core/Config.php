<?php

declare(strict_types=1);

namespace Core;

class Config
{
    private array $items = [];

    public function __construct(string $configPath, ?string $environment = null)
    {
        $this->loadFrom($configPath);

        // Merge environment-specific overrides (config/{env}/*.php)
        $env = $environment ?? ($_ENV['APP_ENV'] ?? null);
        if ($env !== null) {
            $envPath = $configPath . '/' . $env;
            if (is_dir($envPath)) {
                $this->mergeFrom($envPath);
            }
        }
    }

    private function loadFrom(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    private function mergeFrom(string $path): void
    {
        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            $override = require $file;

            if (isset($this->items[$key]) && is_array($this->items[$key]) && is_array($override)) {
                $this->items[$key] = array_replace_recursive($this->items[$key], $override);
            } else {
                $this->items[$key] = $override;
            }
        }
    }

    /**
     * Acessa config com dot-notation: config('app.name'), config('app.debug')
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$this->items;

        foreach (array_slice($segments, 0, -1) as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target[end($segments)] = $value;
    }

    public function has(string $key): bool
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    public function all(): array
    {
        return $this->items;
    }
}
