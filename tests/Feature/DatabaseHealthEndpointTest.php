<?php

use App\Http\Middleware\ThrottleDatabaseHealthRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['cache.health_limiter_store' => 'array']);
});

test('database health endpoint executes a probe query', function () {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([(object) ['?column?' => 1]]);

    $this->getJson('/api/health/database')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson([
            'status' => 'ok',
            'database' => 'reachable',
        ]);
});

test('database health endpoint returns a sanitized service unavailable response', function () {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andThrow(new RuntimeException('secret-host.example database failure'));

    $response = $this->getJson('/api/health/database')
        ->assertServiceUnavailable()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson([
            'status' => 'error',
            'database' => 'unreachable',
        ]);

    expect($response->getContent())->not->toContain('secret-host.example');
});

test('database health endpoint accepts head requests', function () {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([(object) ['?column?' => 1]]);

    $this->head('/api/health/database')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertContent('');
});

test('database health endpoint is publicly available and throttled', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($route) => $route->uri() === 'api/health/database'
    );

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain('GET', 'HEAD')
        ->and($route->gatherMiddleware())->toContain(ThrottleDatabaseHealthRequests::class)
        ->not->toContain('auth:sanctum');
});

test('database health endpoint limits each client to twelve requests per minute', function () {
    DB::shouldReceive('select')
        ->times(12)
        ->with('SELECT 1')
        ->andReturn([(object) ['?column?' => 1]]);

    for ($attempt = 1; $attempt <= 12; $attempt++) {
        $this->getJson('/api/health/database')->assertOk();
    }

    $this->getJson('/api/health/database')
        ->assertTooManyRequests()
        ->assertHeader('Cache-Control', 'no-store, private');
});
