# Mapeamento do projeto (VeloPHP)

Projeto PHP minimalista (PHP >= 8.2) com um **micro-framework** completo: roteador HTTP com groups/middleware pipeline/route constraints/model binding, container de DI (PSR-11), request imutável (PSR-7 style)/response com content negotiation, validação, config centralizada (com merge por ambiente), tratamento global de exceções, sessions, CSRF, autenticação, security headers, logger, cache, CLI (15 cmds), events, database/query builder/repository pattern, view engine, rate limiting, testes (Pest — 67 testes), análise estática (PHPStan) e CI.

## Como rodar (dev)

- Instalar dependências: `composer install`
- Subir servidor via CLI: `php velo serve`
- Ou manualmente: `php -S 127.0.0.1:8000 -t public`
- Acessar: `http://127.0.0.1:8000`

## Estrutura de diretórios

- `public/`
  - `index.php`: front controller — registra middleware global, carrega rotas e executa `$app->run()`.
  - `.htaccess`: rewrite (Apache) para sempre cair no `index.php` quando não for arquivo/pasta.
- `bootstrap/`
  - `app.php`: cria a instância do App, carrega config e registra service providers.
- `core/` (núcleo do framework)
  - `App.php`: bootstrap do container/roteador, load de `.env`, config, providers e ciclo `run()`.
  - `Router.php`: registro/resolve de rotas com groups, prefix, named routes, route constraints (`{id:\d+}`), model binding, middleware aliases, middleware pipeline e detecção 405.
  - `Route.php`: classe embutida em Router.php — representa uma rota com name() e middleware().
  - `Container.php`: DI container PSR-11 (`ContainerInterface`) com auto-resolução via Reflection + `singleton()`, `has()`, `get()`.
  - `Config.php`: repositório de config com dot-notation (`config('app.name')`) e merge por ambiente (`config/{env}/`).
  - `Request.php`: captura de dados do request + `validate()`, `only()`, `except()`, `withHeader()`, `withQuery()`, `withMethod()`, `withBody()` (imutável, PSR-7 style).
  - `Response.php`: construção/envio da resposta + helpers `json/html/text/redirect` + `negotiate()` (content negotiation) + `prefersJson()`.
  - `ExceptionHandler.php`: tratamento global de erros (404/405/500, debug/production, JSON/HTML).
  - `ServiceProvider.php`: classe abstrata base para providers (`register()` + `boot()`).
  - `helpers.php`: funções globais (`app()`, `config()`, `request()`, `response()`, `abort()`, `route()`, `redirect()`).
  - `Middleware/`
    - `MiddlewareInterface.php`: interface para middleware com `handle($request, $response, $next)`.
    - `CorsMiddleware.php`: middleware CORS usando config centralizada.
    - `SecurityHeadersMiddleware.php`: middleware de security headers (X-Content-Type-Options, X-Frame-Options, HSTS, CSP, etc.).
    - `RequestIdMiddleware.php`: gera UUID v4 por request (header X-Request-Id) para correlação de logs.
  - `Exceptions/`
    - `HttpException.php`: exceção HTTP com status code (usada por `abort()`).
    - `ValidationException.php`: exceção de validação com array de erros.
    - `ContainerNotFoundException.php`: exceção PSR-11 (NotFoundExceptionInterface).
  - `Validation/`
    - `Validator.php`: validador com regras (required, email, min, max, numeric, in, regex, etc.).
  - `Session.php`: gerenciador de sessões com flash messages.
  - `Log/`
    - `Logger.php`: logger PSR-3 style com rotação diária.
  - `Cache/`
    - `CacheInterface.php`: interface de cache.
    - `FileStore.php`: cache file-based com TTL e `remember()`.
  - `Auth/`
    - `Auth.php`: gerenciador de autenticação (login, logout, check, attempt, hash).
  - `Event/`
    - `Dispatcher.php`: sistema de eventos com listen/dispatch.
  - `Database/`
    - `Connection.php`: wrapper PDO com prepared statements e transações.
    - `QueryBuilder.php`: query builder fluente (select, where, insert, update, delete).
    - `DB.php`: facade estática para acesso ao banco.
    - `RepositoryInterface.php`: interface para repositórios (find, all, create, update, delete, query).
    - `Repository.php`: classe abstrata base com CRUD, findBy, findAllBy, exists, count, paginate.
  - `View/`
    - `Engine.php`: template engine PHP com layouts, sections e includes.
  - `Console/`
    - `Application.php`: runner CLI.
    - `Command.php`: classe abstrata de comando.
    - `Commands/`: Serve, RouteList, RouteCache, RouteClear, MakeController, MakeMiddleware, MakeProvider, MakeListener, MakeView, CacheClear, DbTest.
- `app/`
  - `Controllers/`: controllers da aplicação.
  - `Providers/`
    - `AppServiceProvider.php`: provider principal da aplicação.
  - `Middleware/`
    - `AuthMiddleware.php`: exemplo de middleware de autenticação via Bearer token.
  - `Listeners/`: event listeners da aplicação.
  - `Models/`: reservado para modelos (vazio atualmente).
- `config/`
  - `app.php`: configuração da aplicação (name, env, debug, providers).
  - `cors.php`: configuração de CORS (origin, methods, headers).
  - `session.php`: configuração de sessão (lifetime, path, secure, samesite).
  - `logging.php`: configuração de log (level, path).
  - `cache.php`: configuração de cache (driver, path, ttl).
  - `database.php`: configuração do banco de dados (driver, host, port, database, credentials).
  - `view.php`: configuração do view engine (path, extension).
  - `security.php`: configuração de security headers (X-Frame-Options, HSTS, CSP, Permissions-Policy).
- `routes/`
  - `web.php`: rotas web (HTML).
  - `api.php`: rotas API (auto-prefixadas com `/api`).
- `storage/`
  - `logs/`: diretório para logs.
  - `cache/`: diretório para cache.
- `vendor/`: dependências do Composer (gerado).
- `composer.json`: autoload PSR-4, helpers e dependências.
- `.env` / `.env.example`: variáveis de ambiente.

## Autoload e namespaces

Definido em `composer.json`:

- `Core\\` → `core/`
- `App\\` → `app/`
- `files` → `core/helpers.php` (funções globais)

Depois de criar novas classes, rode `composer dump-autoload` se necessário.

## Fluxo de execução (request → response)

1. Servidor web aponta o document root para `public/`.
2. Rewrites (Apache) encaminham rotas para `public/index.php`.
3. `public/index.php`:
   - inclui `vendor/autoload.php`;
   - inclui `bootstrap/app.php` (cria App, carrega .env, config, providers);
   - registra middleware global (ex.: `CorsMiddleware`);
   - chama `$app->loadRoutes()` (carrega `routes/web.php` e `routes/api.php`);
   - chama `$app->run()`.
4. `Core\App::run()`:
   - resolve `Request` via Container;
   - chama `Router::resolve()` dentro de try/catch;
   - Router executa pipeline de middleware → handler → Response;
   - se houver exceção, `ExceptionHandler::renderException()` gera a resposta de erro;
   - envia a resposta via `Response::send()`.

## Rotas

Definidas em `routes/web.php` e `routes/api.php`:

### Web (`routes/web.php`)
- `GET /` → closure → HTML "Bem-vindo ao VeloPHP!" (name: `home`)
- `GET /about` → `HomeController::about()` (name: `about`)

### API (`routes/api.php` — auto-prefixado com `/api`)
- `GET /api/status` → `ApiController::status()` (name: `api.status`)
- `GET /api/users/{id}` → `ApiController::showUser($id)` (name: `api.users.show`)
- `POST /api/data` → closure → JSON com `allInput()` (name: `api.data`)

## Features do Router

### Route Groups
```php
$router->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
});

// Fluent API
$router->prefix('/api/v2')->middleware(AuthMiddleware::class)->group(function($router) {
    $router->get('/users', [UserController::class, 'index']);
});
```

### Route Constraints (Regex)
```php
// Restringe {id} a apenas dígitos
$router->get('/users/{id:\d+}', [UserController::class, 'show']);

// Slug com letras minúsculas, números e hífens
$router->get('/posts/{slug:[a-z0-9-]+}', [PostController::class, 'show']);

// Múltiplos constraints
$router->get('/api/{version:[v]\d+}/users/{id:\d+}', [ApiController::class, 'show']);

// Sem constraint = default [a-zA-Z0-9_-]+
$router->get('/users/{name}', [UserController::class, 'byName']);
```

### Named Routes
```php
$router->get('/users/{id:\d+}', [UserController::class, 'show'])->name('users.show');

// Gerar URL (constraints são ignorados na geração)
$url = route('users.show', ['id' => 42]); // "/users/42"
```

### Per-Route Middleware
```php
$router->get('/admin', [AdminController::class, 'index'])->middleware(AuthMiddleware::class);
```

### Route Caching
```bash
php velo route:cache    # Gera cache (storage/cache/routes.php)
php velo route:clear    # Remove cache
```
Rotas com closure são ignoradas no cache (apenas `[Controller::class, 'method']`).
Quando o cache existe, `loadRoutes()` carrega dele em vez de ler os arquivos.

### Model Binding
```php
// Registra resolver: quando {user} aparece, busca no banco
$router->bind('user', fn($id) => DB::table('users')->where('id', $id)->first());

// Ou usa um Repository
$router->bind('post', App\Repositories\PostRepository::class);

// Na rota, o parâmetro já chega como array (registro do banco)
$router->get('/users/{user}', function($req, $res, $user) {
    return $res->json($user); // $user = ['id' => 5, 'name' => '...']
});
// Se não encontrar, retorna 404 automaticamente
```

### Middleware Aliases
```php
// Registra aliases para nomes curtos
$router->aliasMiddleware('auth', \Core\Middleware\AuthGuard::class);
$router->aliasMiddleware('guest', \Core\Middleware\GuestMiddleware::class);

// Ou em batch
$router->aliasMiddleware([
    'auth' => \Core\Middleware\AuthGuard::class,
    'guest' => \Core\Middleware\GuestMiddleware::class,
    'throttle' => \Core\Middleware\RateLimitMiddleware::class,
]);

// Usa por nome curto
$router->get('/admin', [AdminController::class, 'index'])->middleware('auth');
```

### 405 Method Not Allowed
Se o path existe mas o método HTTP não bate, retorna 405 em vez de 404.

## Middleware Pipeline (PSR-15 style)

Middleware implementa `MiddlewareInterface`:
```php
class MyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Antes do handler
        $response = $next($request, $response);
        // Depois do handler
        return $response;
    }
}
```

Ordem de execução: middleware global → middleware do grupo → middleware da rota → handler.

## Config Repository

Arquivos PHP em `config/` são carregados automaticamente. Acesso via dot-notation:
```php
config('app.name');        // "VeloPHP"
config('app.debug');       // true/false
config('cors.origin');     // "*"
```

### Config por Ambiente

Crie `config/{env}/*.php` para sobrescrever valores por ambiente. O merge é recursivo (`array_replace_recursive`).

```
config/
  app.php              ← base (debug: true)
  production/
    app.php            ← override (debug: false)
```

O ambiente é detectado automaticamente via `APP_ENV` no `.env`:
```env
APP_ENV=production
```

Valores do ambiente sobrescrevem os da base; valores não presentes no override são preservados.

## Validação

```php
$validated = $request->validate([
    'email' => 'required|email',
    'name' => 'required|string|min:3|max:100',
    'age' => 'numeric|min:18',
]);
```

Regras: `required`, `string`, `numeric`, `integer`, `email`, `url`, `boolean`, `array`, `min:N`, `max:N`, `in:a,b,c`, `regex:/pattern/`, `confirmed`, `nullable`.

Erro retorna 422 com JSON de erros.

## Exception Handler

- **Debug mode** (`APP_DEBUG=true`): mostra exception class, mensagem, arquivo e stack trace.
- **Production mode**: mostra apenas status code e label genérica.
- Rotas `/api/*` ou requests com `Accept: application/json` recebem erro em JSON.
- Rotas web recebem página HTML estilizada.

## Helper Functions

| Função | Descrição |
|--------|-----------|
| `app()` | Instância do App ou resolve binding |
| `config($key, $default)` | Acessa config com dot-notation |
| `request()` | Instância atual do Request |
| `response()` | Instância do Response |
| `abort($code, $msg)` | Lança HttpException |
| `route($name, $params)` | Gera URL de rota nomeada |
| `redirect($url, $status)` | Response de redirect |
| `session($key, $default)` | Acessa sessão (sem args retorna Session) |
| `csrf_token()` | Retorna o CSRF token |
| `csrf_field()` | Retorna input hidden com CSRF token |
| `cache($key, $default)` | Acessa cache (sem args retorna store) |
| `logger()` | Retorna instância do Logger |
| `log_message($level, $msg)` | Loga mensagem |
| `event($name, $data)` | Dispara evento (sem args retorna Dispatcher) |
| `db($table)` | QueryBuilder para tabela (sem args retorna Connection) |
| `view($name, $data)` | Renderiza view como Response |
| `e($value)` | Escapa HTML (XSS prevention) |
| `old($key, $default)` | Valor anterior do input (formulários) |
| `auth()` | Retorna o Auth manager |

## Service Providers

```php
class MyProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->container()->singleton(MyService::class);
    }

    public function boot(): void
    {
        // Após todos os providers registrados
    }
}
```

Providers são listados em `config/app.php` → `providers`.

## Sessions

```php
// Nas helpers
session('key');                    // Lê valor
session()->set('key', 'value');    // Escreve
session()->flash('msg', 'Salvo!'); // Flash (disponível só no próximo request)
session()->destroy();              // Destrói sessão
session()->regenerate();           // Regenera ID (pós-login)
```

Config em `config/session.php`.

## CSRF Protection

Middleware `CsrfMiddleware` valida token `_token` em POST/PUT/PATCH/DELETE (exceto rotas `/api/*`).

```php
// No formulário HTML
<form method="POST" action="/submit">
    <?= csrf_field() ?>
    ...
</form>

// Via JavaScript (header)
fetch('/submit', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': '<?= csrf_token() ?>' },
});
```

CSRF já está ativo no middleware global em `public/index.php`.

## Logger

```php
logger()->info('Usuário {user} logou', ['user' => 'admin']);
logger()->error('Falha no pagamento', ['order_id' => 123]);
log_message('warning', 'Disco quase cheio');
```

Logs em `storage/logs/velophp-YYYY-MM-DD.log`. Config em `config/logging.php`.

Níveis: emergency, alert, critical, error, warning, notice, info, debug.

## Cache

```php
cache()->set('users', $data, 600);        // TTL 600s
cache()->get('users');                      // Retorna dados ou null
cache()->has('users');                      // bool
cache()->delete('users');                   // Remove
cache()->flush();                           // Limpa tudo
cache()->remember('stats', 300, fn() => computeStats()); // Get or compute
```

Config em `config/cache.php`. Limpar via CLI: `php velo cache:clear`.

## Events

```php
// Registrar listener
event()->listen('user.registered', function ($data) {
    logger()->info('Novo usuario: {name}', $data);
});

// Disparar evento
event('user.registered', ['name' => 'John', 'email' => 'john@test.com']);
```

## Database / Query Builder

```php
use Core\Database\DB;

// Select
$users = DB::table('users')->where('active', 1)->orderBy('name')->get();
$user = DB::table('users')->where('id', 1)->first();

// Insert
$id = DB::table('users')->insert(['name' => 'John', 'email' => 'john@test.com']);

// Update
DB::table('users')->where('id', 1)->update(['name' => 'Jane']);

// Delete
DB::table('users')->where('id', 1)->delete();

// Raw query
$results = DB::select('SELECT * FROM users WHERE id = ?', [1]);

// Transação
DB::transaction(function ($db) {
    $db->table('accounts')->where('id', 1)->update(['balance' => 100]);
    $db->table('accounts')->where('id', 2)->update(['balance' => 200]);
});

// Helper
$users = db('users')->where('status', 'active')->get();
```

Config em `config/database.php`. Testar conexão: `php velo db:test`.

## Repository Pattern

Abstrai acesso ao banco por trás de uma interface com CRUD padrão:

```php
// Criar: php velo make:repository User
// Gera app/Repositories/UserRepository.php

namespace App\Repositories;

use Core\Database\Repository;

class UserRepository extends Repository
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
}
```

Uso:
```php
$repo = app(UserRepository::class);

$user = $repo->find(1);               // SELECT * FROM users WHERE id = 1
$all = $repo->all();                   // SELECT * FROM users
$id = $repo->create(['name' => 'Jo']); // INSERT
$repo->update(1, ['name' => 'Jane']);  // UPDATE WHERE id = 1
$repo->delete(1);                      // DELETE WHERE id = 1

// Extras
$repo->findBy('email', 'jo@test.com'); // WHERE email = ?
$repo->findAllBy('role', 'admin');     // WHERE role = ? (retorna array)
$repo->exists(1);                      // bool
$repo->count();                        // int
$repo->paginate(page: 2, perPage: 20); // {data, total, per_page, current_page, last_page}

// QueryBuilder personalizado
$repo->query()->where('active', 1)->orderBy('name')->get();
```

## View Engine

Templates PHP em `resources/views/` com extensão `.velo.php`.

```php
// No controller/rota
return view('welcome', ['title' => 'Home', 'name' => 'VeloPHP']);
```

**Layout** (`resources/views/layouts/main.velo.php`):
```php
<!DOCTYPE html>
<html>
<head><title><?= e($title) ?></title></head>
<body>
    <?= $__engine->yield('content') ?>
</body>
</html>
```

**Page** (`resources/views/welcome.velo.php`):
```php
<?php $__engine->extends('layouts/main'); ?>
<?php $__engine->section('content'); ?>
    <h1>Bem-vindo ao <?= e($name) ?>!</h1>
<?php $__engine->endSection(); ?>
```

**Include**:
```php
<?= $__engine->include('partials/header', ['title' => 'Meu Site']) ?>
```

## PSR-11 Container

O container implementa `Psr\Container\ContainerInterface`:

```php
use Psr\Container\ContainerInterface;

$container = app()->container(); // instanceof ContainerInterface

$container->get('service');     // PSR-11: lança NotFoundExceptionInterface se não existe
$container->has('service');     // PSR-11: verifica existência
$container->make('service');    // VeloPHP: auto-resolve mesmo sem binding registrado
```

## Immutable Request (PSR-7 Style)

Métodos `with*()` retornam nova instância sem alterar a original:

```php
$request = request();

// Adicionar/substituir header
$new = $request->withHeader('X-Custom', 'value');

// Substituir query params
$new = $request->withQuery(['page' => 2, 'per_page' => 20]);

// Alterar método HTTP
$new = $request->withMethod('POST');

// Substituir body
$new = $request->withBody('{"key":"value"}');

// Encadear (original não é alterada)
$new = $request->withMethod('PUT')->withHeader('Authorization', 'Bearer token')->withBody($json);
```

## Content Negotiation

Resposta automática baseada no header `Accept` do request:

```php
// No controller
return $response->negotiate($request, [
    'json' => ['users' => $users],
    'html' => view('users/index', ['users' => $users])->body(),
    'text' => 'Lista de usuários',
]);
// Accept: application/json → JSON
// Accept: text/html → HTML
// Accept: */* → HTML (fallback)
// Nenhum formato disponível → 406

// Helper estático
if (Response::prefersJson($request)) {
    return $response->json($data);
}
```

## RequestId Middleware

Gera UUID v4 por request para correlação de logs:

```php
// Registrar globalmente em public/index.php
$router->use(\Core\Middleware\RequestIdMiddleware::class);

// Header X-Request-Id é adicionado à response automaticamente
// Se o request já tiver X-Request-Id (ex: load balancer), será preservado
```

## Security Headers

Middleware `SecurityHeadersMiddleware` adiciona headers de segurança automaticamente:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security` (quando HSTS habilitado)
- `Content-Security-Policy` (quando configurado)
- `Permissions-Policy` (quando configurado)

Config em `config/security.php`. HSTS habilitado via `HSTS_ENABLED=true` no `.env`.

## Rate Limiting

```php
// Em public/index.php ou route group
$router->use(\Core\Middleware\RateLimitMiddleware::class);

// Ou por rota
$router->get('/api/expensive', [Controller::class, 'method'])
    ->middleware(\Core\Middleware\RateLimitMiddleware::class);
```

Default: 60 requests/minuto por IP. Retorna 429 + header `Retry-After`.

## CLI (`php velo`)

| Comando | Descrição |
|---------|-----------|
| `serve [host] [port]` | Inicia servidor dev (default: 127.0.0.1:8000) |
| `route:list` | Lista todas as rotas com método, path, name, middleware |
| `route:cache` | Gera cache das rotas para produção |
| `route:clear` | Remove o cache de rotas |
| `make:controller <Name>` | Cria controller em `app/Controllers/` |
| `make:middleware <Name>` | Cria middleware em `app/Middleware/` |
| `make:provider <Name>` | Cria provider em `app/Providers/` |
| `make:listener <Name>` | Cria listener em `app/Listeners/` |
| `make:repository <Name>` | Cria repository em `app/Repositories/` |
| `make:view <name>` | Cria view em `resources/views/` |
| `cache:clear` | Limpa arquivos de cache |
| `db:test` | Testa conexão com o banco de dados |
| `test` | Executa testes com Pest |
| `analyse` | Executa PHPStan (análise estática) |
| `lint` | Verifica/corrige estilo com Pint |

## Autenticação

Sistema de auth session-based com login, registro e logout.

```php
// Verificar se está logado
if (auth()->check()) {
    $user = auth()->user(); // array com dados do usuário
    $id = auth()->id();
}

// Login por credenciais
if (auth()->attempt($email, $password)) {
    // Logou com sucesso
}

// Logout
auth()->logout();

// Hash de senha
$hash = \Core\Auth\Auth::hash('minhasenha');
$valido = \Core\Auth\Auth::verify('minhasenha', $hash);
```

### Rotas de auth (routes/auth.php)
| Método | Path | Name | Descrição |
|--------|------|------|-----------|
| GET | `/login` | `login` | Formulário de login |
| POST | `/login` | `login.submit` | Processa login |
| GET | `/register` | `register` | Formulário de cadastro |
| POST | `/register` | `register.submit` | Processa cadastro |
| POST | `/logout` | `logout` | Logout |

### Guards
- `AuthGuard` — protege rotas autenticadas (web redireciona para `/login`, API retorna 401)
- `GuestMiddleware` — permite apenas visitantes (redireciona logados para `/`)

```php
$router->get('/dashboard', [DashController::class, 'index'])
    ->middleware(\Core\Middleware\AuthGuard::class);
```

Config em `config/auth.php` (tabela, chave primária, coluna de senha).

## DX (Qualidade de Código)

### Testes
```bash
php velo test          # Executa Pest
vendor/bin/pest        # Direto
```
Testes em `tests/Unit/` — Router, Container, Config, Validator, Request, Response (67 testes).

### Análise Estática
```bash
php velo analyse       # PHPStan level 5
vendor/bin/phpstan analyse
```

### Code Style
```bash
php velo lint          # Pint (PSR-12) — corrige automaticamente
php velo lint --test   # Apenas verifica
```

### CI (GitHub Actions)
Pipeline em `.github/workflows/ci.yml`:
1. `composer validate --strict`
2. `composer install`
3. `vendor/bin/pint --test`
4. `vendor/bin/phpstan analyse`
5. `vendor/bin/pest`
6. `composer audit`

Roda em PHP 8.2, 8.3 e 8.4.

## Dependências

**Produção:**
- `vlucas/phpdotenv` — carregamento de `.env`
- `psr/container` — PSR-11 ContainerInterface

**Desenvolvimento:**
- `pestphp/pest` — framework de testes
- `phpstan/phpstan` — análise estática
- `laravel/pint` — code style (PSR-12)
