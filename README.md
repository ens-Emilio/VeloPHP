<h1 align="center">⚡ VeloPHP</h1>

<p align="center">
  <strong>Micro-framework PHP moderno, rápido e elegante.</strong>
  <br>
  Rotas, migrations, ORM relacional, CLI, container DI, validação e muito mais — zero bloat.
</p>

<p align="center">
  <a href="https://github.com/ens-Emilio/VeloPHP/actions"><img src="https://github.com/ens-Emilio/VeloPHP/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue" alt="License"></a>
</p>

---

## ✨ Features

- **Rotas expressivas** — grupos, prefix, named routes, constraints regex, model binding
- **Middleware pipeline** — CORS, CSRF, rate limit, auth guard, security headers
- **Container PSR-11** — auto-resolução via Reflection, singletons, service providers
- **Database** — QueryBuilder fluente, Repository pattern, migrations com rollback, seeders
- **Schema Builder** — Blueprint DDL tipo Laravel, `php velo make:migration`
- **Joins & Relacionamentos** — belongsTo, hasMany, belongsToMany com eager loading
- **Soft Deletes** — trait opt-in no Repository
- **CLI completo** — 25+ comandos: `serve`, `make:*`, `migrate`, `db:seed`, `route:list`
- **Validação** — required, email, min, max, numeric, in, url, confirmed
- **Views** — Engine própria com escapes automáticos e layouts
- **Cache** — FileStore, cache tagging
- **Autenticação** — Auth facade, session-based, guards
- **Segurança** — CSRF, security headers, rate limiting, CORS configurável
- **PSR-4 autoloading** — namespaces `Core\` e `App\`

## 🚀 Quickstart

```bash
# 1. Crie o projeto
composer create-project velo/php meu-app

# 2. Configure o ambiente
cd meu-app
cp .env.example .env
# edite .env com suas credenciais de banco

# 3. Inicie o servidor
php velo serve
```

Acesse **http://localhost:8000** 🎉

### Instalação interativa

```bash
php -r "copy('https://raw.githubusercontent.com/ens-Emilio/VeloPHP/main/installer/create.php', 'create-velophp');" && php create-velophp
```

## 📋 Pré-requisitos

- PHP >= 8.2
- PDO extension (pdo_mysql)
- Composer

## 🧱 Estrutura

```
meu-app/
├── app/           # Controllers, Models, Middleware, Providers
├── bootstrap/     # Bootstrap da aplicação
├── config/        # Configurações (app, database, auth, cache...)
├── core/          # Núcleo do framework
├── database/      # Migrations e seeders
├── public/        # Front controller (index.php)
├── resources/     # Views
├── routes/        # Definição de rotas
├── storage/       # Cache, logs
└── velo           # CLI entry point
```

## 🧪 Testes

```bash
vendor/bin/pest          # Rodar testes
vendor/bin/phpstan       # Análise estática
vendor/bin/pint --test   # Verificar estilo
```

## 📖 Comandos CLI

| Comando | Descrição |
|---------|-----------|
| `php velo serve` | Inicia servidor de desenvolvimento |
| `php velo migrate` | Executa migrations pendentes |
| `php velo make:migration create_users_table` | Cria migration |
| `php velo make:controller UserController` | Cria controller |
| `php velo make:model User` | Cria model |
| `php velo db:seed` | Executa seeders |
| `php velo route:list` | Lista rotas registradas |
| `php velo test` | Executa testes |

Veja a [referência completa](https://ens-Emilio.github.io/php/cli).

## 🤝 Contribuindo

Contribuições são bem-vindas! Veja [CONTRIBUTING.md](CONTRIBUTING.md).

## 📄 Licença

MIT © VeloPHP
