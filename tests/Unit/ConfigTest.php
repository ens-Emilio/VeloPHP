<?php

declare(strict_types=1);

use Core\Config;

beforeEach(function () {
    // Cria config temporária
    $this->tmpDir = sys_get_temp_dir() . '/velophp_test_config_' . uniqid();
    mkdir($this->tmpDir);

    file_put_contents($this->tmpDir . '/app.php', '<?php return ["name" => "TestApp", "debug" => true, "nested" => ["key" => "value"]];');
    file_put_contents($this->tmpDir . '/db.php', '<?php return ["host" => "localhost", "port" => 3306];');

    $this->config = new Config($this->tmpDir);
});

afterEach(function () {
    // Limpa env overrides se existirem
    $envDir = $this->tmpDir . '/production';
    if (is_dir($envDir)) {
        array_map('unlink', glob($envDir . '/*.php'));
        rmdir($envDir);
    }
    array_map('unlink', glob($this->tmpDir . '/*.php'));
    rmdir($this->tmpDir);
});

test('config carrega arquivos PHP', function () {
    expect($this->config->get('app.name'))->toBe('TestApp');
    expect($this->config->get('db.host'))->toBe('localhost');
});

test('config suporta dot-notation aninhada', function () {
    expect($this->config->get('app.nested.key'))->toBe('value');
});

test('config retorna default quando chave não existe', function () {
    expect($this->config->get('inexistente', 'fallback'))->toBe('fallback');
    expect($this->config->get('app.inexistente', 42))->toBe(42);
});

test('config has() verifica existência', function () {
    expect($this->config->has('app.name'))->toBeTrue();
    expect($this->config->has('app.naoexiste'))->toBeFalse();
});

test('config set() define valor', function () {
    $this->config->set('app.new_key', 'new_value');
    expect($this->config->get('app.new_key'))->toBe('new_value');
});

test('config all() retorna tudo', function () {
    $all = $this->config->all();
    expect($all)->toHaveKeys(['app', 'db']);
});

// --- Environment-specific config ---

test('config merge de ambiente sobrescreve valores', function () {
    $envDir = $this->tmpDir . '/production';
    mkdir($envDir);
    file_put_contents($envDir . '/app.php', '<?php return ["debug" => false, "nested" => ["extra" => "prod"]];');

    $config = new Config($this->tmpDir, 'production');

    // Sobrescrito
    expect($config->get('app.debug'))->toBeFalse();
    // Preservado do base
    expect($config->get('app.name'))->toBe('TestApp');
    // Merged recursivamente
    expect($config->get('app.nested.key'))->toBe('value');
    expect($config->get('app.nested.extra'))->toBe('prod');
});

test('config sem diretório de ambiente usa apenas base', function () {
    $config = new Config($this->tmpDir, 'staging');
    expect($config->get('app.name'))->toBe('TestApp');
    expect($config->get('app.debug'))->toBeTrue();
});
