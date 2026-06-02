<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'VeloPHP') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f0f1a; color: #e0e0e0; min-height: 100vh; }
        .container { max-width: 960px; margin: 0 auto; padding: 2rem; }
        header { background: #1a1a2e; border-bottom: 2px solid #16213e; padding: 1rem 2rem; }
        header h1 { color: #00d4ff; font-size: 1.5rem; }
        nav a { color: #a0a0a0; text-decoration: none; margin-right: 1rem; }
        nav a:hover { color: #00d4ff; }
        main { padding: 2rem 0; }
        footer { text-align: center; padding: 2rem; color: #666; font-size: 0.85rem; }
    </style>
</head>
<body>
    <header>
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <h1><?= e(config('app.name')) ?></h1>
            <nav>
                <a href="<?= route('home') ?>">Home</a>
                <a href="<?= route('about') ?>">Sobre</a>
                <?php if (auth()->check()): ?>
                    <span style="color: #666;">|</span>
                    <span style="color: #6bff6b;"><?= e(auth()->user()['name'] ?? 'User') ?></span>
                    <form method="POST" action="<?= route('logout') ?>" style="display: inline;">
                        <?= csrf_field() ?>
                        <button type="submit" style="background: none; border: none; color: #a0a0a0; cursor: pointer; font-size: inherit; font-family: inherit;">Sair</button>
                    </form>
                <?php else: ?>
                    <span style="color: #666;">|</span>
                    <a href="<?= route('login') ?>">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?= $__engine->yield('content') ?>
    </main>

    <footer>
        <p><?= e(config('app.name')) ?> &copy; <?= date('Y') ?></p>
    </footer>
</body>
</html>
