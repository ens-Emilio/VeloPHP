<?php

declare(strict_types=1);

namespace Core\Log;

class Logger
{
    private const LEVELS = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7,
    ];

    private int $threshold;

    public function __construct(
        private readonly string $logPath,
        string $level = 'debug',
    ) {
        $this->threshold = self::LEVELS[$level] ?? 7;

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $levelValue = self::LEVELS[$level] ?? 7;

        if ($levelValue > $this->threshold) {
            return;
        }

        $message = $this->interpolate($message, $context);

        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        $line = "[{$timestamp}] {$levelUpper}: {$message}" . PHP_EOL;

        $filename = $this->logPath . '/velophp-' . date('Y-m-d') . '.log';
        file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Interpola placeholders {key} com valores do contexto.
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            if (is_string($value) || is_numeric($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replacements['{' . $key . '}'] = (string) $value;
            } elseif ($value instanceof \Throwable) {
                $replacements['{' . $key . '}'] = $value->getMessage() . ' in ' . $value->getFile() . ':' . $value->getLine();
            } else {
                $replacements['{' . $key . '}'] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return strtr($message, $replacements);
    }
}
