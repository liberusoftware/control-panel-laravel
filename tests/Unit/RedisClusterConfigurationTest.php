<?php

beforeEach(function (): void {
    $this->clusterEnvironment = [];
    foreach (['REDIS_CACHE_CLUSTER_NODES', 'REDIS_CACHE_CLUSTER_PASSWORD'] as $key) {
        $this->clusterEnvironment[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null];
        unset($_ENV[$key], $_SERVER[$key]);
    }
});

afterEach(function (): void {
    foreach ($this->clusterEnvironment as $key => [$env, $server]) {
        unset($_ENV[$key], $_SERVER[$key]);
        if ($env !== null) {
            $_ENV[$key] = $env;
        }
        if ($server !== null) {
            $_SERVER[$key] = $server;
        }
    }
});

it('configures TLS Redis or Valkey cache cluster seeds independently of the queue connection', function (): void {
    $_ENV['REDIS_CACHE_CLUSTER_NODES'] = $_SERVER['REDIS_CACHE_CLUSTER_NODES'] = 'tls://cache-0.example:6379,tls://cache-1.example:6379';
    $_ENV['REDIS_CACHE_CLUSTER_PASSWORD'] = $_SERVER['REDIS_CACHE_CLUSTER_PASSWORD'] = 'private-cache-password';
    $config = require base_path('config/database.php');
    $cluster = $config['redis']['clusters']['cache-cluster'];
    expect($cluster[0])->toBe(['scheme' => 'tls', 'host' => 'cache-0.example', 'port' => 6379, 'database' => 0])
        ->and($cluster[1]['host'])->toBe('cache-1.example')
        ->and($cluster['options']['password'])->toBe('private-cache-password')
        ->and($cluster['options']['context']['ssl']['verify_peer'])->toBeTrue()
        ->and($cluster['options']['context']['ssl']['verify_peer_name'])->toBeTrue()
        ->and($config['redis'])->toHaveKeys(['default', 'cache']);
});

it('preserves the existing standalone cache and queue configuration by default', function (): void {
    $config = require base_path('config/database.php');
    expect($config['redis']['clusters'])->toBe([])
        ->and($config['redis'])->toHaveKeys(['default', 'cache']);
});

it('rejects unsafe cluster seed URLs before attempting connections', function (string $urls): void {
    $_ENV['REDIS_CACHE_CLUSTER_NODES'] = $_SERVER['REDIS_CACHE_CLUSTER_NODES'] = $urls;
    expect(fn () => require base_path('config/database.php'))->toThrow(InvalidArgumentException::class);
})->with([
    'tls://one.example:6379',
    'redis://one.example:6379,redis://two.example:6379',
    'tls://user:password@one.example:6379,tls://two.example:6379',
    'tls://one.example:6379/1,tls://two.example:6379',
    'tls://one.example:6379?verify_peer=false,tls://two.example:6379',
]);
