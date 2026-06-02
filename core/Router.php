<?php

declare(strict_types=1);

namespace Core;

use Core\Middleware\MiddlewareInterface;

class Route
{
    public ?string $name = null;
    public array $middleware = [];
    private ?Router $router = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $pattern,
        public readonly mixed $callback,
    ) {
    }

    /** @internal */
    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        $this->router?->registerNamedRoute($name, $this);
        return $this;
    }

    public function middleware(string|array $middleware): static
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }
}

class Router
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, Route> */
    private array $namedRoutes = [];

    /** @var array global middleware classes/callables */
    private array $middlewares = [];

    /** @var array<string, string> middleware aliases: short name → FQCN */
    private array $middlewareAliases = [];

    /** @var array current group state stack */
    private array $groupStack = [];

    /** @var array<string, callable|string> model binding resolvers: param name → resolver */
    private array $modelBindings = [];

    // --- Registro de rotas ---

    public function get(string $path, callable|array $callback): Route
    {
        return $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, callable|array $callback): Route
    {
        return $this->addRoute('POST', $path, $callback);
    }

    public function put(string $path, callable|array $callback): Route
    {
        return $this->addRoute('PUT', $path, $callback);
    }

    public function delete(string $path, callable|array $callback): Route
    {
        return $this->addRoute('DELETE', $path, $callback);
    }

    public function patch(string $path, callable|array $callback): Route
    {
        return $this->addRoute('PATCH', $path, $callback);
    }

    // --- Middleware global ---

    /**
     * Registra middleware global (classe ou callable legado).
     */
    public function use(string|callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Registra aliases de middleware para uso por nome curto.
     *
     * Exemplo: $router->aliasMiddleware('auth', AuthGuard::class)
     * Depois: $router->get('/admin', ...)->middleware('auth')
     *
     * @param string|array<string, string> $name Nome do alias ou array de aliases
     */
    public function aliasMiddleware(string|array $name, ?string $class = null): void
    {
        if (is_array($name)) {
            foreach ($name as $alias => $fqcn) {
                $this->middlewareAliases[$alias] = $fqcn;
            }
            return;
        }

        if ($class !== null) {
            $this->middlewareAliases[$name] = $class;
        }
    }

    // --- Route groups ---

    /**
     * Agrupa rotas com prefix e/ou middleware compartilhados.
     *
     * Exemplo:
     *   $router->group(['prefix' => '/api', 'middleware' => [AuthMiddleware::class]], function($router) {
     *       $router->get('/users', [UserController::class, 'index']);
     *   });
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    /**
     * Atalho: $router->prefix('/api')->group(function($router) { ... })
     */
    public function prefix(string $prefix): GroupBuilder
    {
        return new GroupBuilder($this, ['prefix' => $prefix]);
    }

    /**
     * Registra um model binding: quando {param} aparece na rota, o resolver é chamado com o valor.
     *
     * Uso:
     *   $router->bind('user', fn($id) => DB::table('users')->where('id', $id)->first());
     *   $router->bind('post', App\Repositories\PostRepository::class);
     */
    public function bind(string $param, callable|string $resolver): void
    {
        $this->modelBindings[$param] = $resolver;
    }

    /** @internal */
    public function registerNamedRoute(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    // --- Named route URL generation ---

    public function url(string $name, array $params = []): string
    {
        $route = $this->namedRoutes[$name] ?? null;

        if ($route === null) {
            throw new \RuntimeException("Rota nomeada [{$name}] não encontrada.");
        }

        $path = $route->path;
        foreach ($params as $key => $value) {
            // Substitui tanto {key} quanto {key:constraint}
            $path = preg_replace('/\{' . preg_quote($key, '/') . '(?::[^}]+)?\}/', (string) $value, $path);
        }

        return $path;
    }

    // --- Resolução ---

    public function resolve(string $method, string $uri, ?Container $container = null): mixed
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $method = strtoupper($method);
        $matchedRoute = null;
        $pathMatchedButMethodDidNot = false;
        $routeParams = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route->pattern, $path, $matches)) {
                continue;
            }

            if ($route->method !== $method) {
                $pathMatchedButMethodDidNot = true;
                continue;
            }

            $matchedRoute = $route;
            $routeParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            break;
        }

        $request = $container?->make(Request::class) ?? Request::capture();
        $response = $container?->make(Response::class) ?? new Response();

        // OPTIONS com path conhecido: roda global middleware e retorna (CORS preflight)
        if ($matchedRoute === null && $method === 'OPTIONS' && $pathMatchedButMethodDidNot) {
            return $this->runMiddlewarePipeline(
                $this->middlewares,
                $request,
                $response,
                fn (Request $req, Response $res) => $res->setStatus(204),
                $container
            );
        }

        if ($matchedRoute === null) {
            if ($pathMatchedButMethodDidNot) {
                throw new Exceptions\HttpException(405, "Método {$method} não permitido para {$path}.");
            }
            throw new Exceptions\HttpException(404, "Rota {$path} não encontrada.");
        }

        $request->setRouteParams($routeParams);

        // Model binding: resolve parâmetros registrados
        $resolvedParams = $this->resolveModelBindings($routeParams, $container);

        // Monta pipeline: global middleware + route-specific middleware
        $allMiddleware = array_merge($this->middlewares, $matchedRoute->middleware);

        // Executa pipeline
        $coreHandler = function (Request $request, Response $response) use ($matchedRoute, $container, $resolvedParams): Response {
            $callback = $matchedRoute->callback;
            $paramValues = array_values($resolvedParams);

            if (is_array($callback)) {
                $controller = $container
                    ? $container->make($callback[0])
                    : new $callback[0]();
                $result = call_user_func_array(
                    [$controller, $callback[1]],
                    [$request, $response, ...$paramValues]
                );
            } else {
                $result = call_user_func_array($callback, [$request, $response, ...$paramValues]);
            }

            return $this->prepareReturn($result, $response);
        };

        return $this->runMiddlewarePipeline($allMiddleware, $request, $response, $coreHandler, $container);
    }

    /**
     * Retorna todas as rotas registradas.
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Exporta rotas serializáveis (apenas com callback array, ignora closures).
     *
     * @return array{routes: list<array{method: string, path: string, pattern: string, callback: array, name: string|null, middleware: array}>, skipped: int}
     */
    public function exportRoutes(): array
    {
        $exported = [];
        $skipped = 0;

        foreach ($this->routes as $route) {
            if (!is_array($route->callback)) {
                $skipped++;
                continue;
            }
            $exported[] = [
                'method' => $route->method,
                'path' => $route->path,
                'pattern' => $route->pattern,
                'callback' => $route->callback,
                'name' => $route->name,
                'middleware' => $route->middleware,
            ];
        }

        return ['routes' => $exported, 'skipped' => $skipped];
    }

    /**
     * Importa rotas a partir de um array exportado (cache).
     *
     * @param list<array{method: string, path: string, pattern: string, callback: array, name: string|null, middleware: array}> $routes
     */
    public function importRoutes(array $routes): void
    {
        foreach ($routes as $data) {
            $route = new Route($data['method'], $data['path'], $data['pattern'], $data['callback']);
            $route->setRouter($this);
            $route->middleware = $data['middleware'];

            if ($data['name'] !== null) {
                $route->name = $data['name'];
                $this->namedRoutes[$data['name']] = $route;
            }

            $this->routes[] = $route;
        }
    }

    // --- Internals ---

    private function addRoute(string $method, string $path, callable|array $callback): Route
    {
        $path = $this->applyGroupPrefix($path);
        $groupMiddleware = $this->getGroupMiddleware();

        // Suporta {param:regex} (ex: {id:\d+}) e {param} (default: [a-zA-Z0-9_-]+)
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)(?::([^}]+))?\}/',
            fn (array $m) => '(?P<' . $m[1] . '>' . ($m[2] ?? '[a-zA-Z0-9_-]+') . ')',
            $path
        );
        $pattern = '#^' . $pattern . '$#';

        $route = new Route($method, $path, $pattern, $callback);
        $route->setRouter($this);
        $route->middleware = $groupMiddleware;

        $this->routes[] = $route;

        return $route;
    }

    private function applyGroupPrefix(string $path): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= rtrim($group['prefix'], '/');
            }
        }

        if ($prefix === '') {
            return $path;
        }

        return $prefix . ($path === '/' ? '' : $path);
    }

    private function getGroupMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $mw = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $middleware = array_merge($middleware, $mw);
            }
        }
        return $middleware;
    }

    private function runMiddlewarePipeline(array $middlewares, Request $request, Response $response, callable $destination, ?Container $container): Response
    {
        $aliases = $this->middlewareAliases;

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function (callable $next, string|callable $middleware) use ($container, $aliases): callable {
                return function (Request $request, Response $response) use ($middleware, $next, $container, $aliases): Response {
                    // Classe que implementa MiddlewareInterface (lazy: só instancia quando executa)
                    if (is_string($middleware)) {
                        // Resolve alias se existir
                        $class = $aliases[$middleware] ?? $middleware;
                        /** @var MiddlewareInterface $instance */
                        $instance = $container ? $container->make($class) : new $class();
                        return $instance->handle($request, $response, $next);
                    }

                    // Callable legado (compatibilidade com middlewares antigos)
                    $result = $middleware($request, $response, $next);

                    if ($result === false) {
                        return $response;
                    }

                    if ($result instanceof Response) {
                        return $result;
                    }

                    return $next($request, $response);
                };
            },
            $destination
        );

        return $pipeline($request, $response);
    }

    /**
     * Resolve model bindings para os parâmetros da rota.
     */
    private function resolveModelBindings(array $params, ?Container $container): array
    {
        foreach ($params as $name => $value) {
            if (!isset($this->modelBindings[$name])) {
                continue;
            }

            $resolver = $this->modelBindings[$name];

            // Resolver é uma classe de Repository — usa find()
            if (is_string($resolver) && class_exists($resolver)) {
                /** @var \Core\Database\RepositoryInterface $repo */
                $repo = $container ? $container->make($resolver) : new $resolver();
                $model = $repo->find($value);
            } else {
                $model = $resolver($value);
            }

            if ($model === null) {
                throw new Exceptions\HttpException(404, "Registro não encontrado para [{$name}] = {$value}.");
            }

            $params[$name] = $model;
        }

        return $params;
    }

    private function prepareReturn(mixed $result, Response $response): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            $response->setBody($result);
            return $response;
        }

        return $response;
    }
}

/**
 * Builder fluente para groups: $router->prefix('/api')->middleware([...])->group(fn)
 */
class GroupBuilder
{
    private array $attributes;

    public function __construct(
        private readonly Router $router,
        array $attributes = [],
    ) {
        $this->attributes = $attributes;
    }

    public function prefix(string $prefix): static
    {
        $this->attributes['prefix'] = ($this->attributes['prefix'] ?? '') . $prefix;
        return $this;
    }

    public function middleware(string|array $middleware): static
    {
        $mw = is_array($middleware) ? $middleware : [$middleware];
        $this->attributes['middleware'] = array_merge($this->attributes['middleware'] ?? [], $mw);
        return $this;
    }

    public function group(callable $callback): void
    {
        $this->router->group($this->attributes, $callback);
    }
}
