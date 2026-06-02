<?php

declare(strict_types=1);

namespace Core\Cache;

class FileStore implements CacheInterface
{
    public function __construct(
        private readonly string $path,
        private readonly int $defaultTtl = 3600,
    ) {
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->filePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires_at'] !== 0 && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl ??= $this->defaultTtl;

        $data = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0,
        ];

        file_put_contents($this->filePath($key), serialize($data), LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    public function delete(string $key): void
    {
        $file = $this->filePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function flush(): void
    {
        $files = glob($this->path . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Retorna o valor cacheado ou executa o callback e armazena o resultado.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    private function filePath(string $key): string
    {
        return $this->path . '/' . sha1($key) . '.cache';
    }
}
