<?php

declare(strict_types=1);

namespace Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    public function setStatus(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function status(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function json(mixed $data, int $status = 200): static
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'application/json';
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function html(string $html, int $status = 200): static
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
        $this->body = $html;
        return $this;
    }

    public function text(string $text, int $status = 200): static
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'text/plain; charset=UTF-8';
        $this->body = $text;
        return $this;
    }

    public function redirect(string $url, int $status = 302): static
    {
        $this->statusCode = $status;
        $this->headers['Location'] = $url;
        return $this;
    }

    /**
     * Content negotiation: escolhe formato baseado no Accept header do request.
     *
     * @param array{json?: mixed, html?: string, text?: string} $formats Formatos disponíveis
     */
    public function negotiate(Request $request, array $formats, int $status = 200): static
    {
        $accept = strtolower($request->header('accept', 'text/html'));

        if (isset($formats['json']) && str_contains($accept, 'application/json')) {
            return $this->json($formats['json'], $status);
        }

        if (isset($formats['html']) && (str_contains($accept, 'text/html') || str_contains($accept, '*/*'))) {
            return $this->html($formats['html'], $status);
        }

        if (isset($formats['text'])) {
            return $this->text($formats['text'], $status);
        }

        // Fallback: primeiro formato disponível
        if (isset($formats['json'])) {
            return $this->json($formats['json'], $status);
        }

        if (isset($formats['html'])) {
            return $this->html($formats['html'], $status);
        }

        return $this->setStatus(406);
    }

    /**
     * Verifica se o request prefere JSON (Accept header).
     */
    public static function prefersJson(Request $request): bool
    {
        $accept = strtolower($request->header('accept', ''));
        return str_contains($accept, 'application/json');
    }

    /**
     * Envia os headers e o body para o cliente.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }

    public function __toString(): string
    {
        return $this->body;
    }
}
