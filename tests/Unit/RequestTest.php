<?php

declare(strict_types=1);

use Core\Request;

function createTestRequest(): Request
{
    return new Request(
        method: 'GET',
        uri: '/test?foo=bar',
        path: '/test',
        query: ['foo' => 'bar'],
        post: [],
        server: ['REQUEST_METHOD' => 'GET'],
        headers: ['content-type' => 'text/html'],
        body: '',
    );
}

test('withHeader retorna nova instância com header adicionado', function () {
    $request = createTestRequest();
    $new = $request->withHeader('X-Custom', 'value');

    expect($new)->not->toBe($request);
    expect($new->header('x-custom'))->toBe('value');
    expect($request->header('x-custom'))->toBeNull();
});

test('withHeader substitui header existente', function () {
    $request = createTestRequest();
    $new = $request->withHeader('Content-Type', 'application/json');

    expect($new->header('content-type'))->toBe('application/json');
    expect($request->header('content-type'))->toBe('text/html');
});

test('withQuery retorna nova instância com query substituída', function () {
    $request = createTestRequest();
    $new = $request->withQuery(['baz' => 'qux']);

    expect($new)->not->toBe($request);
    expect($new->query('baz'))->toBe('qux');
    expect($new->query('foo'))->toBeNull();
    expect($request->query('foo'))->toBe('bar');
});

test('withMethod retorna nova instância com método alterado', function () {
    $request = createTestRequest();
    $new = $request->withMethod('POST');

    expect($new)->not->toBe($request);
    expect($new->method())->toBe('POST');
    expect($request->method())->toBe('GET');
});

test('withBody retorna nova instância com body substituído', function () {
    $request = createTestRequest();
    $new = $request->withBody('{"key":"value"}');

    expect($new)->not->toBe($request);
    expect($new->rawBody())->toBe('{"key":"value"}');
    expect($request->rawBody())->toBe('');
});

test('encadeamento imutável preserva todas as mudanças', function () {
    $request = createTestRequest();
    $new = $request
        ->withMethod('PUT')
        ->withHeader('Authorization', 'Bearer token123')
        ->withBody('data');

    expect($new->method())->toBe('PUT');
    expect($new->header('authorization'))->toBe('Bearer token123');
    expect($new->rawBody())->toBe('data');
    // Original inalterado
    expect($request->method())->toBe('GET');
    expect($request->header('authorization'))->toBeNull();
});
