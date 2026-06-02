<?php

declare(strict_types=1);

use Core\Container;

test('container pode registrar e resolver binding', function () {
    $container = new Container();
    $container->bind('greeting', fn () => new stdClass());

    $result = $container->make('greeting');
    expect($result)->toBeInstanceOf(stdClass::class);
});

test('container resolve singleton uma vez só', function () {
    $container = new Container();
    $container->singleton('counter', fn () => (object) ['count' => random_int(1, 999999)]);

    $a = $container->make('counter');
    $b = $container->make('counter');

    expect($a)->toBe($b);
    expect($a->count)->toBe($b->count);
});

test('container registra instância existente', function () {
    $container = new Container();
    $obj = new stdClass();
    $obj->name = 'test';

    $container->instance('myobj', $obj);
    expect($container->make('myobj'))->toBe($obj);
});

test('container auto-resolve classes sem binding', function () {
    $container = new Container();
    $result = $container->make(stdClass::class);
    expect($result)->toBeInstanceOf(stdClass::class);
});

test('container has() verifica existência de binding', function () {
    $container = new Container();
    $container->bind('foo', fn () => new stdClass());

    expect($container->has('foo'))->toBeTrue();
    expect($container->has('bar'))->toBeFalse();
});

test('container lança exceção para classe inexistente', function () {
    $container = new Container();
    $container->make('ClasseInexistente123');
})->throws(ReflectionException::class);

// --- PSR-11 ---

test('container implementa PSR-11 ContainerInterface', function () {
    $container = new Container();
    expect($container)->toBeInstanceOf(\Psr\Container\ContainerInterface::class);
});

test('container get() retorna binding registrado', function () {
    $container = new Container();
    $container->bind('psr11test', fn () => new stdClass());

    $result = $container->get('psr11test');
    expect($result)->toBeInstanceOf(stdClass::class);
});

test('container get() lança NotFoundExceptionInterface para binding inexistente', function () {
    $container = new Container();

    try {
        $container->get('naoexiste');
    } catch (\Throwable $e) {
        expect($e)->toBeInstanceOf(\Psr\Container\NotFoundExceptionInterface::class);
        return;
    }

    throw new \RuntimeException('Esperava NotFoundExceptionInterface');
});
