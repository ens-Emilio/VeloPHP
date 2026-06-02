<?php

declare(strict_types=1);

use Core\Request;
use Core\Response;

function createRequestWithAccept(string $accept): Request
{
    return new Request(
        method: 'GET',
        uri: '/test',
        path: '/test',
        query: [],
        post: [],
        server: [],
        headers: ['accept' => $accept],
        body: '',
    );
}

// --- Content Negotiation ---

test('negotiate retorna JSON quando Accept é application/json', function () {
    $request = createRequestWithAccept('application/json');
    $response = new Response();

    $response->negotiate($request, [
        'json' => ['status' => 'ok'],
        'html' => '<p>ok</p>',
    ]);

    expect($response->body())->toBe('{"status":"ok"}');
});

test('negotiate retorna HTML quando Accept é text/html', function () {
    $request = createRequestWithAccept('text/html');
    $response = new Response();

    $response->negotiate($request, [
        'json' => ['status' => 'ok'],
        'html' => '<p>ok</p>',
    ]);

    expect($response->body())->toBe('<p>ok</p>');
});

test('negotiate retorna HTML quando Accept é */*', function () {
    $request = createRequestWithAccept('*/*');
    $response = new Response();

    $response->negotiate($request, [
        'json' => ['status' => 'ok'],
        'html' => '<p>ok</p>',
    ]);

    expect($response->body())->toBe('<p>ok</p>');
});

test('negotiate retorna text como fallback', function () {
    $request = createRequestWithAccept('text/plain');
    $response = new Response();

    $response->negotiate($request, [
        'text' => 'ok',
    ]);

    expect($response->body())->toBe('ok');
});

test('negotiate retorna 406 se nenhum formato disponível', function () {
    $request = createRequestWithAccept('application/xml');
    $response = new Response();

    $response->negotiate($request, []);

    expect($response->status())->toBe(406);
});

test('prefersJson detecta Accept: application/json', function () {
    $request = createRequestWithAccept('application/json');
    expect(Response::prefersJson($request))->toBeTrue();

    $request2 = createRequestWithAccept('text/html');
    expect(Response::prefersJson($request2))->toBeFalse();
});

// --- RequestId Middleware ---

test('RequestIdMiddleware gera UUID v4', function () {
    $id = \Core\Middleware\RequestIdMiddleware::generateId();

    // UUID v4 format: 8-4-4-4-12 hex chars
    expect($id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

test('RequestIdMiddleware gera IDs únicos', function () {
    $ids = [];
    for ($i = 0; $i < 100; $i++) {
        $ids[] = \Core\Middleware\RequestIdMiddleware::generateId();
    }
    expect(array_unique($ids))->toHaveCount(100);
});
