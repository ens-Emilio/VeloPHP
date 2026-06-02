<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpException;
use Core\Exceptions\ValidationException;

class ExceptionHandler
{
    public function __construct(
        private readonly bool $debug = false,
    ) {
    }

    public function register(): void
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
    }

    public function handleError(int $severity, string $message, string $file, int $line): never
    {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(\Throwable $e): void
    {
        $statusCode = $this->resolveStatusCode($e);
        $isJson = $this->wantsJson();

        if ($e instanceof ValidationException) {
            $this->sendValidationError($e, $isJson);
            return;
        }

        if ($isJson) {
            $this->sendJsonError($e, $statusCode);
        } else {
            $this->sendHtmlError($e, $statusCode);
        }
    }

    public function renderException(\Throwable $e): Response
    {
        $statusCode = $this->resolveStatusCode($e);
        $response = new Response();

        if ($e instanceof ValidationException) {
            return $response->json([
                'error' => 'Validation Error',
                'messages' => $e->errors(),
            ], 422);
        }

        if ($this->wantsJson()) {
            $data = ['error' => $this->resolveMessage($e, $statusCode)];
            if ($this->debug) {
                $data['exception'] = $e::class;
                $data['file'] = $e->getFile() . ':' . $e->getLine();
                $data['trace'] = array_slice(
                    array_map(
                        fn ($t) => ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?') . ' ' . ($t['class'] ?? '') . ($t['type'] ?? '') . $t['function'],
                        $e->getTrace()
                    ),
                    0,
                    15
                );
            }
            return $response->json($data, $statusCode);
        }

        if ($this->debug) {
            $html = $this->renderDebugHtml($e, $statusCode);
        } else {
            $html = $this->renderProductionHtml($statusCode);
        }

        return $response->html($html, $statusCode);
    }

    private function resolveStatusCode(\Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }
        if ($e instanceof ValidationException) {
            return 422;
        }
        return 500;
    }

    private function resolveMessage(\Throwable $e, int $statusCode): string
    {
        if ($e instanceof HttpException && $e->getMessage() !== '') {
            return $e->getMessage();
        }

        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }

    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || str_starts_with(parse_url($uri, PHP_URL_PATH) ?? '', '/api');
    }

    private function sendValidationError(ValidationException $e, bool $isJson): void
    {
        http_response_code(422);

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Validation Error',
                'messages' => $e->errors(),
            ], JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<h1>422 - Dados inválidos</h1>';
            echo '<ul>';
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $msg) {
                    echo '<li><strong>' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . ':</strong> '
                        . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</li>';
                }
            }
            echo '</ul>';
        }
    }

    private function sendJsonError(\Throwable $e, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $data = ['error' => $this->resolveMessage($e, $statusCode)];

        if ($this->debug) {
            $data['exception'] = $e::class;
            $data['file'] = $e->getFile() . ':' . $e->getLine();
            $data['trace'] = array_slice(
                array_map(
                    fn ($t) => ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?') . ' ' . ($t['class'] ?? '') . ($t['type'] ?? '') . $t['function'],
                    $e->getTrace()
                ),
                0,
                15
            );
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function sendHtmlError(\Throwable $e, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');

        if ($this->debug) {
            echo $this->renderDebugHtml($e, $statusCode);
        } else {
            echo $this->renderProductionHtml($statusCode);
        }
    }

    private function renderDebugHtml(\Throwable $e, int $statusCode): string
    {
        $title = htmlspecialchars($e::class, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8');
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head><meta charset="UTF-8"><title>{$statusCode} - {$title}</title>
        <style>
            body { font-family: system-ui, sans-serif; margin: 2rem; background: #1a1a2e; color: #e0e0e0; }
            h1 { color: #ff6b6b; } h2 { color: #ffd93d; }
            .box { background: #16213e; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #ff6b6b; }
            pre { background: #0f3460; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.85rem; line-height: 1.6; }
            code { color: #e2e2e2; }
        </style></head>
        <body>
            <h1>{$statusCode} - {$title}</h1>
            <div class="box"><h2>Mensagem</h2><p>{$message}</p></div>
            <div class="box"><h2>Arquivo</h2><p><code>{$file}</code></p></div>
            <div class="box"><h2>Stack Trace</h2><pre><code>{$trace}</code></pre></div>
        </body></html>
        HTML;
    }

    private function renderProductionHtml(int $statusCode): string
    {
        // Tenta usar view customizada se existir
        $viewFile = (getcwd() ?: '.') . '/../resources/views/errors/' . $statusCode . '.velo.php';
        if (!file_exists($viewFile)) {
            // Tenta caminho relativo ao basePath
            $basePaths = [
                dirname($_SERVER['DOCUMENT_ROOT'] ?? '') . '/resources/views/errors/' . $statusCode . '.velo.php',
                dirname(__DIR__) . '/resources/views/errors/' . $statusCode . '.velo.php',
            ];
            foreach ($basePaths as $path) {
                if (file_exists($path)) {
                    return file_get_contents($path);
                }
            }
        } else {
            return file_get_contents($viewFile);
        }

        $label = $this->resolveMessage(new HttpException($statusCode), $statusCode);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head><meta charset="UTF-8"><title>{$statusCode} - {$label}</title>
        <style>
            body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center;
                   min-height: 100vh; margin: 0; background: #1a1a2e; color: #e0e0e0; }
            .center { text-align: center; }
            h1 { font-size: 4rem; margin: 0; color: #ff6b6b; }
            p { font-size: 1.25rem; color: #a0a0a0; }
        </style></head>
        <body><div class="center"><h1>{$statusCode}</h1><p>{$label}</p></div></body></html>
        HTML;
    }
}
