<?php

declare(strict_types=1);

use Core\Exceptions\ValidationException;
use Core\Validation\Validator;

test('validação required passa com valor preenchido', function () {
    $validator = new Validator(['name' => 'João'], ['name' => 'required']);
    $result = $validator->validate();
    expect($result)->toBe(['name' => 'João']);
});

test('validação required falha com valor vazio', function () {
    $validator = new Validator(['name' => ''], ['name' => 'required']);
    $validator->validate();
})->throws(ValidationException::class);

test('validação email passa com email válido', function () {
    $validator = new Validator(['email' => 'test@example.com'], ['email' => 'required|email']);
    $result = $validator->validate();
    expect($result['email'])->toBe('test@example.com');
});

test('validação email falha com email inválido', function () {
    $validator = new Validator(['email' => 'not-an-email'], ['email' => 'email']);
    $validator->validate();
})->throws(ValidationException::class);

test('validação min:3 verifica tamanho mínimo de string', function () {
    $validator = new Validator(['name' => 'ab'], ['name' => 'min:3']);
    $validator->validate();
})->throws(ValidationException::class);

test('validação max:5 verifica tamanho máximo de string', function () {
    $validator = new Validator(['code' => 'abcdef'], ['code' => 'max:5']);
    $validator->validate();
})->throws(ValidationException::class);

test('validação numeric passa com número', function () {
    $validator = new Validator(['age' => '25'], ['age' => 'numeric']);
    $result = $validator->validate();
    expect($result['age'])->toBe('25');
});

test('validação numeric falha com texto', function () {
    $validator = new Validator(['age' => 'abc'], ['age' => 'numeric']);
    $validator->validate();
})->throws(ValidationException::class);

test('validação in aceita valores permitidos', function () {
    $validator = new Validator(['role' => 'admin'], ['role' => 'in:admin,user,editor']);
    $result = $validator->validate();
    expect($result['role'])->toBe('admin');
});

test('validação in rejeita valores não permitidos', function () {
    $validator = new Validator(['role' => 'hacker'], ['role' => 'in:admin,user']);
    $validator->validate();
})->throws(ValidationException::class);

test('validação retorna apenas campos validados', function () {
    $validator = new Validator(
        ['name' => 'João', 'email' => 'joao@test.com', 'extra' => 'ignorado'],
        ['name' => 'required', 'email' => 'required|email'],
    );
    $result = $validator->validate();
    expect($result)->toBe(['name' => 'João', 'email' => 'joao@test.com']);
    expect($result)->not->toHaveKey('extra');
});

test('validação múltiplas regras com pipe', function () {
    $validator = new Validator(
        ['name' => 'Jo'],
        ['name' => 'required|string|min:3'],
    );
    $validator->validate();
})->throws(ValidationException::class);

test('ValidationException contém array de erros', function () {
    try {
        $validator = new Validator(['name' => ''], ['name' => 'required']);
        $validator->validate();
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('name');
        expect($e->errors()['name'])->toBeArray();
    }
});

test('validação url aceita URLs válidas', function () {
    $validator = new Validator(['site' => 'https://example.com'], ['site' => 'url']);
    $result = $validator->validate();
    expect($result['site'])->toBe('https://example.com');
});

test('validação confirmed verifica confirmação', function () {
    $validator = new Validator(
        ['password' => '123456', 'password_confirmation' => '123456'],
        ['password' => 'confirmed'],
    );
    $result = $validator->validate();
    expect($result)->toHaveKey('password');
});

test('validação confirmed falha sem confirmação', function () {
    $validator = new Validator(
        ['password' => '123456', 'password_confirmation' => 'diferente'],
        ['password' => 'confirmed'],
    );
    $validator->validate();
})->throws(ValidationException::class);
