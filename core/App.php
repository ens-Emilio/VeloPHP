<?php

declare(strict_types=1);

namespace Core;

class App
{
    /** @var self|null */
    private static ?App $instance = null;

    private Container $container;
    private Router $router;
    private Config $config;
    private ExceptionHandler $exceptionHandler;

    /** @var ServiceProvider[] */
    private array $providers = [];

    public function __construct(?string $basePath = null)
    {
        self::$instance = $this;

        $this->container = new Container();
        $this->router = new Router();

        // Carrega .env se existir
        if ($basePath !== null) {
            $this->loadEnv($basePath);
        }

        // Carrega config
        $configPath = ($basePath ?? getcwd()) . '/config';
        $this->config = new Config($configPath);

        // Exception handler
        $this->exceptionHandler = new ExceptionHandler(
            debug: $this->config->get('app.debug', false),
        );
        $this->exceptionHandler->register();

        // Registra singletons core
        $this->container->singleton(Request::class, fn () => Request::capture());
        $this->container->singleton(Response::class, fn () => new Response());
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(self::class, $this);
        $this->container->instance(Router::class, $this->router);
        $this->container->instance(Config::class, $this->config);
        $this->container->instance(ExceptionHandler::class, $this->exceptionHandler);

        // Session
        $this->container->singleton(Session::class, fn () => new Session(
            $this->config->get('session', []),
        ));

        // Logger
        $this->container->singleton(Log\Logger::class, fn () => new Log\Logger(
            logPath: $this->config->get('logging.path', ($basePath ?? getcwd()) . '/storage/logs'),
            level: $this->config->get('logging.level', 'debug'),
        ));

        // Cache
        $this->container->singleton(Cache\CacheInterface::class, fn () => new Cache\FileStore(
            path: $this->config->get('cache.path', ($basePath ?? getcwd()) . '/storage/cache'),
            defaultTtl: $this->config->get('cache.ttl', 3600),
        ));

        // Events
        $this->container->singleton(Event\Dispatcher::class);

        // Database (lazy — só conecta quando usado)
        $this->container->singleton(Database\Connection::class, fn () => new Database\Connection(
            driver: $this->config->get('database.driver', 'mysql'),
            host: $this->config->get('database.host', '127.0.0.1'),
            port: $this->config->get('database.port', 3306),
            database: $this->config->get('database.database', 'velophp'),
            username: $this->config->get('database.username', 'root'),
            password: $this->config->get('database.password', ''),
            charset: $this->config->get('database.charset', 'utf8mb4'),
        ));

        // Auth
        $this->container->singleton(Auth\Auth::class, fn () => new Auth\Auth(
            session: $this->container->make(Session::class),
            connection: $this->container->make(Database\Connection::class),
            table: $this->config->get('auth.table', 'users'),
            primaryKey: $this->config->get('auth.primary_key', 'id'),
            passwordColumn: $this->config->get('auth.password_column', 'password'),
        ));

        // View Engine
        $this->container->singleton(View\Engine::class, fn () => new View\Engine(
            viewPath: $this->config->get('view.path', ($basePath ?? getcwd()) . '/resources/views'),
            extension: $this->config->get('view.extension', '.velo.php'),
        ));
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('App não foi inicializado.');
        }
        return self::$instance;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function config(): Config
    {
        return $this->config;
    }

    /**
     * Registra e inicializa os service providers definidos em config/app.php.
     */
    public function bootProviders(): void
    {
        $providerClasses = $this->config->get('app.providers', []);

        // Register phase
        foreach ($providerClasses as $providerClass) {
            $provider = new $providerClass($this);
            $provider->register();
            $this->providers[] = $provider;
        }

        // Boot phase
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }

    /**
     * Carrega rotas de arquivos em routes/ (ou do cache se existir).
     */
    public function loadRoutes(string $basePath): void
    {
        $cacheFile = $basePath . '/storage/cache/routes.php';

        // Se cache existe, importa rotas cacheadas
        if (file_exists($cacheFile)) {
            $cached = require $cacheFile;
            $this->router->importRoutes($cached);
            return;
        }

        $router = $this->router;

        $authRoutes = $basePath . '/routes/auth.php';
        if (file_exists($authRoutes)) {
            require $authRoutes;
        }

        $webRoutes = $basePath . '/routes/web.php';
        if (file_exists($webRoutes)) {
            require $webRoutes;
        }

        $apiRoutes = $basePath . '/routes/api.php';
        if (file_exists($apiRoutes)) {
            $router->group([
                'prefix' => '/api',
            ], function () use ($apiRoutes) {
                require $apiRoutes;
            });
        }
    }

    /**
     * Carrega variáveis de ambiente do arquivo .env via vlucas/phpdotenv (se instalado)
     * ou via parser nativo simples.
     */
    private function loadEnv(string $basePath): void
    {
        $envFile = rtrim($basePath, '/') . '/.env';

        // Tenta usar vlucas/phpdotenv se disponível
        if (class_exists(\Dotenv\Dotenv::class)) {
            $dotenv = \Dotenv\Dotenv::createImmutable($basePath);
            $dotenv->safeLoad();
            return;
        }

        // Fallback: parser nativo básico
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Executa o ciclo de vida da aplicação: resolve a rota e envia a resposta.
     */
    public function run(): void
    {
        try {
            $request = $this->container->make(Request::class);

            $result = $this->router->resolve(
                $request->method(),
                $request->uri(),
                $this->container
            );

            if ($result instanceof Response) {
                $result->send();
            } else {
                echo (string) $result;
            }
        } catch (\Throwable $e) {
            $response = $this->exceptionHandler->renderException($e);
            $response->send();
        }
    }
}
