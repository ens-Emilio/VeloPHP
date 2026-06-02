<?php

declare(strict_types=1);

use Core\App;
use Core\Config;
use Core\Exceptions\HttpException;
use Core\Request;
use Core\Response;
use Core\Router;

if (!function_exists('app')) {
    /**
     * Retorna a instância da aplicação ou resolve um binding do container.
     */
    function app(?string $abstract = null): mixed
    {
        $instance = App::getInstance();

        if ($abstract === null) {
            return $instance;
        }

        return $instance->container()->make($abstract);
    }
}

if (!function_exists('config')) {
    /**
     * Acessa configuração com dot-notation.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return app(Config::class)->get($key, $default);
    }
}

if (!function_exists('request')) {
    /**
     * Retorna a instância atual do Request.
     */
    function request(): Request
    {
        return app(Request::class);
    }
}

if (!function_exists('response')) {
    /**
     * Retorna a instância do Response.
     */
    function response(): Response
    {
        return app(Response::class);
    }
}

if (!function_exists('abort')) {
    /**
     * Lança uma HttpException com o status code informado.
     */
    function abort(int $code, string $message = ''): never
    {
        throw new HttpException($code, $message);
    }
}

if (!function_exists('route')) {
    /**
     * Gera a URL de uma rota nomeada.
     */
    function route(string $name, array $params = []): string
    {
        return app(Router::class)->url($name, $params);
    }
}

if (!function_exists('redirect')) {
    /**
     * Retorna uma Response de redirect.
     */
    function redirect(string $url, int $status = 302): Response
    {
        return response()->redirect($url, $status);
    }
}

if (!function_exists('session')) {
    /**
     * Acessa a sessão. Sem args retorna o Session, com key retorna o valor.
     */
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = app(\Core\Session::class);

        if ($key === null) {
            return $session;
        }

        return $session->get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Retorna o CSRF token da sessão atual.
     */
    function csrf_token(): string
    {
        $session = app(\Core\Session::class);
        $session->start();

        if (!$session->has('_csrf_token')) {
            $session->set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return $session->get('_csrf_token');
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Retorna um input hidden com o CSRF token.
     */
    function csrf_field(): string
    {
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }
}

if (!function_exists('cache')) {
    /**
     * Acessa o cache. Sem args retorna o store, com key retorna o valor.
     */
    function cache(?string $key = null, mixed $default = null): mixed
    {
        $store = app(\Core\Cache\CacheInterface::class);

        if ($key === null) {
            return $store;
        }

        return $store->get($key, $default);
    }
}

if (!function_exists('logger')) {
    /**
     * Retorna a instância do Logger.
     */
    function logger(): \Core\Log\Logger
    {
        return app(\Core\Log\Logger::class);
    }
}

if (!function_exists('log_message')) {
    /**
     * Loga uma mensagem no nível especificado.
     */
    function log_message(string $level, string $message, array $context = []): void
    {
        logger()->log($level, $message, $context);
    }
}

if (!function_exists('auth')) {
    /**
     * Retorna o Auth manager ou o usuário autenticado.
     */
    function auth(): \Core\Auth\Auth
    {
        return app(\Core\Auth\Auth::class);
    }
}

if (!function_exists('event')) {
    /**
     * Retorna o dispatcher ou dispara um evento.
     */
    function event(?string $eventName = null, mixed $data = null): mixed
    {
        $dispatcher = app(\Core\Event\Dispatcher::class);

        if ($eventName === null) {
            return $dispatcher;
        }

        return $dispatcher->dispatch($eventName, $data);
    }
}

if (!function_exists('db')) {
    /**
     * Retorna a conexão com o banco ou um QueryBuilder para a tabela.
     */
    function db(?string $table = null): mixed
    {
        $connection = app(\Core\Database\Connection::class);

        if ($table === null) {
            return $connection;
        }

        return $connection->table($table);
    }
}

if (!function_exists('view')) {
    /**
     * Renderiza um template e retorna como Response.
     */
    function view(string $name, array $data = [], int $status = 200): Response
    {
        $engine = app(\Core\View\Engine::class);
        $html = $engine->render($name, $data);
        return response()->html($html, $status);
    }
}

if (!function_exists('e')) {
    /**
     * Escapa HTML para prevenir XSS.
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('old')) {
    /**
     * Retorna valor anterior do input (útil em formulários após validação falhar).
     */
    function old(string $key, mixed $default = null): mixed
    {
        return session('_old_input.' . $key, $default);
    }
}
