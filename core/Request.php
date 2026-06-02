<?php

declare(strict_types=1);

namespace Core;

use Core\Validation\Validator;

class Request
{
    private array $routeParams = [];

    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $server,
        private readonly array $headers,
        private readonly string $body,
    ) {
    }

    /**
     * Cria uma instância de Request a partir das superglobais do PHP.
     */
    public static function capture(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            uri: $uri,
            path: $path,
            query: $_GET,
            post: $_POST,
            server: $_SERVER,
            headers: $headers,
            body: file_get_contents('php://input') ?: '',
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function allQuery(): array
    {
        return $this->query;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->jsonBody()[$key] ?? $default;
    }

    public function allInput(): array
    {
        return array_merge($this->post, $this->jsonBody());
    }

    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function allHeaders(): array
    {
        return $this->headers;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function rawBody(): string
    {
        return $this->body;
    }

    public function jsonBody(): array
    {
        $decoded = json_decode($this->body, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function allParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Retorna nova instância com header adicional/substituído (imutável, PSR-7 style).
     */
    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[strtolower($name)] = $value;

        return new self(
            method: $this->method,
            uri: $this->uri,
            path: $this->path,
            query: $this->query,
            post: $this->post,
            server: $this->server,
            headers: $headers,
            body: $this->body,
        );
    }

    /**
     * Retorna nova instância com query params substituídos (imutável, PSR-7 style).
     */
    public function withQuery(array $query): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            path: $this->path,
            query: $query,
            post: $this->post,
            server: $this->server,
            headers: $this->headers,
            body: $this->body,
        );
    }

    /**
     * Retorna nova instância com método HTTP alterado (imutável, PSR-7 style).
     */
    public function withMethod(string $method): self
    {
        return new self(
            method: $method,
            uri: $this->uri,
            path: $this->path,
            query: $this->query,
            post: $this->post,
            server: $this->server,
            headers: $this->headers,
            body: $this->body,
        );
    }

    /**
     * Retorna nova instância com body substituído (imutável, PSR-7 style).
     */
    public function withBody(string $body): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            path: $this->path,
            query: $this->query,
            post: $this->post,
            server: $this->server,
            headers: $this->headers,
            body: $body,
        );
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    /**
     * Retorna apenas as chaves especificadas do input.
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->allInput(), array_flip($keys));
    }

    /**
     * Retorna o input excluindo as chaves especificadas.
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->allInput(), array_flip($keys));
    }

    /**
     * Valida o input e retorna os dados validados.
     *
     * @throws \Core\Exceptions\ValidationException
     */
    public function validate(array $rules): array
    {
        $validator = new Validator($this->allInput(), $rules);
        return $validator->validate();
    }
}
