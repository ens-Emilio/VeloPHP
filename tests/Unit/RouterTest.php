<?php

declare(strict_types=1);

use Core\Container;
use Core\Exceptions\HttpException;
use Core\Request;
use Core\Response;
use Core\Router;

function createTestContainer(): Container
{
    $container = new Container();
    $container->singleton(Request::class, fn () => Request::capture());
    $container->singleton(Response::class, fn () => new Response());
    return $container;
}

test('router registra e resolve rota GET', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->get('/test', fn ($req, $res) => $res->json(['ok' => true]));

    $result = $router->resolve('GET', '/test', $container);
    expect($result)->toBeInstanceOf(Response::class);
});

test('router extrai parâmetros da rota', function () {
    $router = new Router();
    $container = createTestContainer();

    $capturedId = null;
    $router->get('/users/{id}', function ($req, $res, $id) use (&$capturedId) {
        $capturedId = $id;
        return $res->json(['id' => $id]);
    });

    $router->resolve('GET', '/users/42', $container);
    expect($capturedId)->toBe('42');
});

test('router lança 404 para rota inexistente', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->resolve('GET', '/nonexistent', $container);
})->throws(HttpException::class);

test('router lança 405 para método incorreto', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->get('/only-get', fn ($req, $res) => $res->text('ok'));

    try {
        $router->resolve('POST', '/only-get', $container);
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(405);
        return;
    }

    throw new RuntimeException('Esperava HttpException 405');
});

test('router suporta named routes', function () {
    $router = new Router();
    $router->get('/users/{id}', fn () => null)->name('users.show');

    $url = $router->url('users.show', ['id' => 99]);
    expect($url)->toBe('/users/99');
});

test('router suporta groups com prefix', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->group(['prefix' => '/api'], function ($router) {
        $router->get('/status', fn ($req, $res) => $res->json(['api' => true]));
    });

    $result = $router->resolve('GET', '/api/status', $container);
    expect($result)->toBeInstanceOf(Response::class);
});

test('router groups aninhados acumulam prefix', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->group(['prefix' => '/api'], function ($router) {
        $router->group(['prefix' => '/v2'], function ($router) {
            $router->get('/users', fn ($req, $res) => $res->json(['v2' => true]));
        });
    });

    $result = $router->resolve('GET', '/api/v2/users', $container);
    expect($result)->toBeInstanceOf(Response::class);
});

test('router getRoutes retorna todas as rotas', function () {
    $router = new Router();
    $router->get('/a', fn () => null);
    $router->post('/b', fn () => null);
    $router->put('/c', fn () => null);

    expect($router->getRoutes())->toHaveCount(3);
});

// --- Route Constraints ---

test('router suporta constraint regex {id:\d+}', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->get('/users/{id:\d+}', fn ($req, $res) => $res->json(['ok' => true]));

    $result = $router->resolve('GET', '/users/42', $container);
    expect($result)->toBeInstanceOf(Response::class);
});

test('router rejeita valor que não bate com constraint', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->get('/users/{id:\d+}', fn ($req, $res) => $res->json(['ok' => true]));

    $router->resolve('GET', '/users/abc', $container);
})->throws(HttpException::class);

test('router constraint extrai parâmetro corretamente', function () {
    $router = new Router();
    $container = createTestContainer();

    $capturedSlug = null;
    $router->get('/posts/{slug:[a-z0-9-]+}', function ($req, $res, $slug) use (&$capturedSlug) {
        $capturedSlug = $slug;
        return $res->text('ok');
    });

    $router->resolve('GET', '/posts/my-first-post', $container);
    expect($capturedSlug)->toBe('my-first-post');
});

test('router named route com constraint gera URL correta', function () {
    $router = new Router();
    $router->get('/users/{id:\d+}', fn () => null)->name('users.show');

    $url = $router->url('users.show', ['id' => 99]);
    expect($url)->toBe('/users/99');
});

test('router suporta múltiplos params com constraints', function () {
    $router = new Router();
    $container = createTestContainer();

    $captured = [];
    $router->get('/api/{version:[v]\d+}/users/{id:\d+}', function ($req, $res, $version, $id) use (&$captured) {
        $captured = ['version' => $version, 'id' => $id];
        return $res->text('ok');
    });

    $router->resolve('GET', '/api/v2/users/123', $container);
    expect($captured)->toBe(['version' => 'v2', 'id' => '123']);
});

// --- Route Cache (export/import) ---

test('router exporta rotas com callback array', function () {
    $router = new Router();
    $router->get('/users', ['App\Controllers\UserController', 'index'])->name('users.index');

    $result = $router->exportRoutes();
    expect($result['routes'])->toHaveCount(1);
    expect($result['skipped'])->toBe(0);
    expect($result['routes'][0]['name'])->toBe('users.index');
    expect($result['routes'][0]['callback'])->toBe(['App\Controllers\UserController', 'index']);
});

test('router export ignora closures', function () {
    $router = new Router();
    $router->get('/closure', fn ($req, $res) => $res->text('ok'));
    $router->get('/array', ['App\Controllers\TestController', 'index']);

    $result = $router->exportRoutes();
    expect($result['routes'])->toHaveCount(1);
    expect($result['skipped'])->toBe(1);
});

test('router importa rotas e resolve corretamente', function () {
    $router = new Router();
    $container = createTestContainer();

    $exported = [
        [
            'method' => 'GET',
            'path' => '/test',
            'pattern' => '#^/test$#',
            'callback' => fn ($req, $res) => $res->json(['imported' => true]),
            'name' => 'test.route',
            'middleware' => [],
        ],
    ];

    $router->importRoutes($exported);
    expect($router->getRoutes())->toHaveCount(1);

    $url = $router->url('test.route');
    expect($url)->toBe('/test');
});

// --- Model Binding ---

test('router model binding resolve parâmetro via callable', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->bind('user', fn ($id) => ['id' => $id, 'name' => 'User ' . $id]);

    $capturedUser = null;
    $router->get('/users/{user}', function ($req, $res, $user) use (&$capturedUser) {
        $capturedUser = $user;
        return $res->json($user);
    });

    $router->resolve('GET', '/users/5', $container);
    expect($capturedUser)->toBe(['id' => '5', 'name' => 'User 5']);
});

test('router model binding lança 404 quando resolver retorna null', function () {
    $router = new Router();
    $container = createTestContainer();

    $router->bind('user', fn ($id) => null);
    $router->get('/users/{user}', fn ($req, $res) => $res->text('ok'));

    $router->resolve('GET', '/users/999', $container);
})->throws(HttpException::class);

// --- Middleware Aliases ---

test('router middleware alias resolve corretamente', function () {
    $router = new Router();
    $container = createTestContainer();

    $executed = false;

    // Registra uma classe middleware inline para teste
    $middlewareClass = new class () implements \Core\Middleware\MiddlewareInterface {
        public static bool $executed = false;

        public function handle(Request $request, Response $response, callable $next): Response
        {
            self::$executed = true;
            return $next($request, $response);
        }
    };

    $fqcn = $middlewareClass::class;
    $container->bind($fqcn, fn () => $middlewareClass);
    $router->aliasMiddleware('test-mw', $fqcn);

    $router->get('/aliased', fn ($req, $res) => $res->text('ok'))->middleware('test-mw');

    $router->resolve('GET', '/aliased', $container);
    expect($middlewareClass::$executed)->toBeTrue();
});

test('router suporta todos os métodos HTTP', function () {
    $router = new Router();
    $container = createTestContainer();

    $methods = ['get', 'post', 'put', 'delete', 'patch'];

    foreach ($methods as $method) {
        $router->$method('/test-' . $method, fn ($req, $res) => $res->text($method));
    }

    foreach ($methods as $method) {
        $result = $router->resolve(strtoupper($method), '/test-' . $method, $container);
        expect($result)->toBeInstanceOf(Response::class);
    }
});
