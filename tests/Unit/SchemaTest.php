<?php

declare(strict_types=1);

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\Grammar;

test('blueprint cria coluna id', function () {
    $blueprint = new Blueprint('users', 'create');
    $blueprint->id();

    $columns = $blueprint->getColumns();
    expect($columns)->toHaveCount(1);
    expect($columns[0]->getName())->toBe('id');
    expect($columns[0]->getType())->toBe('bigint');
});

test('blueprint cria coluna string', function () {
    $blueprint = new Blueprint('users', 'create');
    $blueprint->string('name', 100);

    $columns = $blueprint->getColumns();
    expect($columns[0]->getName())->toBe('name');
    expect($columns[0]->getType())->toBe('varchar');
});

test('grammar compila CREATE TABLE basico', function () {
    $blueprint = new Blueprint('users', 'create');
    $blueprint->id();
    $blueprint->string('name');
    $blueprint->string('email')->unique();
    $blueprint->timestamps();

    $grammar = new Grammar();
    $sql = $grammar->compileCreate($blueprint);

    expect($sql)->toContain('CREATE TABLE users');
    expect($sql)->toContain('id BIGINT UNSIGNED AUTO_INCREMENT');
    expect($sql)->toContain('name VARCHAR(255) NOT NULL');
    expect($sql)->toContain('UNIQUE INDEX');
    expect($sql)->toContain('created_at DATETIME');
    expect($sql)->toContain('updated_at DATETIME');
});

test('grammar compila ALTER TABLE com add column', function () {
    $blueprint = new Blueprint('users', 'alter');
    $blueprint->string('phone')->nullable()->after('email');

    $grammar = new Grammar();
    $statements = $grammar->compileAlter($blueprint);

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toContain('ALTER TABLE users');
    expect($statements[0])->toContain('ADD phone VARCHAR(255)');
    expect($statements[0])->toContain('DEFAULT NULL');
    expect($statements[0])->toContain('AFTER email');
});

test('grammar compila DROP TABLE', function () {
    $grammar = new Grammar();
    expect($grammar->compileDrop('users'))->toBe('DROP TABLE users');
    expect($grammar->compileDropIfExists('users'))->toBe('DROP TABLE IF EXISTS users');
});

test('grammar compila enum column', function () {
    $blueprint = new Blueprint('users', 'create');
    $blueprint->enum('status', ['active', 'inactive', 'archived']);

    $grammar = new Grammar();
    $sql = $grammar->compileCreate($blueprint);

    expect($sql)->toContain("ENUM('active', 'inactive', 'archived')");
});

test('blueprint suporta softDeletes', function () {
    $blueprint = new Blueprint('users', 'create');
    $blueprint->softDeletes();

    $columns = $blueprint->getColumns();
    expect($columns[0]->getName())->toBe('deleted_at');
    expect($columns[0]->getType())->toBe('datetime');
});

test('blueprint suporta foreignId com constrained', function () {
    $blueprint = new Blueprint('posts', 'create');
    $blueprint->foreignId('user_id')->constrained();

    $columns = $blueprint->getColumns();
    $attrs = $columns[0]->getAttributes();

    expect($columns[0]->getType())->toBe('bigint');
    expect($attrs['unsigned'])->toBeTrue();
    expect($attrs['foreign']['table'])->toBe('user');
    expect($attrs['foreign']['reference'])->toBe('id');
});
