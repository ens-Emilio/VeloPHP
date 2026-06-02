#!/usr/bin/env php
<?php

/**
 * VeloPHP Installer — create new VeloPHP projects interactively.
 *
 * Usage: php create.php
 *
 * This script scaffolds a new VeloPHP project with:
 *   - Project directory creation
 *   - Composer create-project from velo/php
 *   - Interactive .env configuration
 *   - Database connection test
 *   - APP_KEY generation
 */

declare(strict_types=1);

// ─── Helpers ────────────────────────────────────────────────────────────────

function color(string $text, string $color): string
{
    $codes = [
        'green' => "\033[32m",
        'cyan' => "\033[36m",
        'yellow' => "\033[33m",
        'red' => "\033[31m",
        'bold' => "\033[1m",
        'reset' => "\033[0m",
    ];

    return ($codes[$color] ?? '') . $text . $codes['reset'];
}

function info(string $msg): void
{
    echo color('• ' . $msg, 'cyan') . PHP_EOL;
}
function success(string $msg): void
{
    echo color('✓ ' . $msg, 'green') . PHP_EOL;
}
function warn(string $msg): void
{
    echo color('! ' . $msg, 'yellow') . PHP_EOL;
}
function error(string $msg): void
{
    echo color('✗ ' . $msg, 'red') . PHP_EOL;
}

function ask(string $question, string $default = ''): string
{
    $display = $default ? " [{$default}]" : '';
    echo color($question . $display . ': ', 'bold');
    $input = trim((string) fgets(STDIN));

    return $input !== '' ? $input : $default;
}

function confirm(string $question, bool $default = true): bool
{
    $hint = $default ? 'Y/n' : 'y/N';
    echo color($question . " [{$hint}]: ", 'bold');
    $input = strtolower(trim((string) fgets(STDIN)));

    if ($input === '') {
        return $default;
    }

    return in_array($input, ['y', 'yes', 's', 'sim'], true);
}

function command_exists(string $cmd): bool
{
    $result = shell_exec("which {$cmd} 2>/dev/null");

    return $result !== null && trim($result) !== '';
}

function run(string $cmd): array
{
    $output = [];
    $exitCode = 0;
    exec($cmd . ' 2>&1', $output, $exitCode);

    return ['output' => $output, 'code' => $exitCode];
}

// ─── Main ───────────────────────────────────────────────────────────────────

echo PHP_EOL;
echo color('  ⚡ VeloPHP Installer', 'cyan') . PHP_EOL;
echo color('  ──────────────────', 'cyan') . PHP_EOL;
echo PHP_EOL;

// ─── 1. Project name ────────────────────────────────────────────────────────

$projectName = ask('Project name', 'my-app');
$projectDir = getcwd() . '/' . $projectName;

if (is_dir($projectDir)) {
    warn("Directory '{$projectName}' already exists.");

    if (!confirm('Overwrite?')) {
        error('Installation cancelled.');

        exit(1);
    }
}

// ─── 2. Verify Composer ─────────────────────────────────────────────────────

info('Checking requirements...');

if (!command_exists('composer')) {
    error('Composer not found. Please install Composer first: https://getcomposer.org');
    exit(1);
}

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    error('PHP 8.2+ is required. Current version: ' . PHP_VERSION);
    exit(1);
}

success('Requirements met.');

// ─── 3. Create project ─────────────────────────────────────────────────────

echo PHP_EOL;
info("Creating project '{$projectName}'...");
echo PHP_EOL;

$result = run("composer create-project velo/php \"{$projectDir}\" --no-interaction 2>&1");

if ($result['code'] !== 0) {
    error('Failed to create project.');
    echo implode(PHP_EOL, $result['output']) . PHP_EOL;
    exit(1);
}

success('Project created.');
echo PHP_EOL;

// ─── 4. Configure .env ────────────────────────────────────────────────────

info('Configuring environment...');

$envPath = $projectDir . '/.env';
$envExamplePath = $projectDir . '/.env.example';

if (!file_exists($envPath) && file_exists($envExamplePath)) {
    copy($envExamplePath, $envPath);
}

// Database configuration
$useDB = confirm('Configure database?');

if ($useDB) {
    $dbType = ask('Database type (mysql)', 'mysql');

    if ($dbType === 'mysql') {
        $dbHost = ask('Database host', '127.0.0.1');
        $dbPort = ask('Database port', '3306');
        $dbName = ask('Database name', str_replace('-', '_', $projectName));
        $dbUser = ask('Database user', 'root');
        $dbPass = ask('Database password', '');

        $envContent = file_get_contents($envPath);
        $envContent = preg_replace('/DB_HOST=.*/', "DB_HOST={$dbHost}", $envContent);
        $envContent = preg_replace('/DB_PORT=.*/', "DB_PORT={$dbPort}", $envContent);
        $envContent = preg_replace('/DB_DATABASE=.*/', "DB_DATABASE={$dbName}", $envContent);
        $envContent = preg_replace('/DB_USERNAME=.*/', "DB_USERNAME={$dbUser}", $envContent);
        $envContent = preg_replace('/DB_PASSWORD=.*/', "DB_PASSWORD={$dbPass}", $envContent);
        file_put_contents($envPath, $envContent);

        // Test connection
        echo PHP_EOL;
        info('Testing database connection...');
        $testResult = run("php \"{$projectDir}/velo\" db:test 2>&1");

        if ($testResult['code'] === 0) {
            success('Database connection OK.');
        } else {
            warn('Could not connect to database. You can configure it later in .env');
        }
    }
}

// Generate APP_KEY
$appKey = bin2hex(random_bytes(32));
$envContent = file_get_contents($envPath);

if (!str_contains($envContent, 'APP_KEY')) {
    $envContent .= PHP_EOL . "APP_KEY={$appKey}" . PHP_EOL;
}

file_put_contents($envPath, $envContent);

success('.env configured.');
echo PHP_EOL;

// ─── 5. Run initial migrations ────────────────────────────────────────────

info('Running initial migrations...');
$migrateResult = run("php \"{$projectDir}/velo\" migrate 2>&1");

if ($migrateResult['code'] === 0) {
    success('Migrations executed.');
} else {
    warn('Migrations skipped. Run `php velo migrate` later.');
}

// ─── 6. Done ───────────────────────────────────────────────────────────────

echo PHP_EOL;
echo color('  ⚡ VeloPHP installed successfully!', 'green') . PHP_EOL;
echo PHP_EOL;
echo color('  Next steps:', 'bold') . PHP_EOL;
echo "    \$ cd {$projectName}" . PHP_EOL;
echo "    \$ php velo serve" . PHP_EOL;
echo PHP_EOL;
echo color('  Access: http://localhost:8000', 'cyan') . PHP_EOL;
echo PHP_EOL;
