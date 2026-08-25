<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Actions\RegisterContainerAsset;
use Liberu\ControlPanel\Containers\ContainersServiceProvider;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(ContainersServiceProvider::class);
    $this->artisan('migrate');
});
it('supports images, registries, networks, volumes, secrets, limits, and lifecycle', function (): void {
    $action = app(RegisterContainerAsset::class);
    $image = $action->execute(['team_id' => 'team-1', 'kind' => 'image', 'repository' => 'registry/app', 'tag' => 'v1']);
    $registry = $action->execute(['team_id' => 'team-1', 'kind' => 'registry', 'name' => 'private', 'endpoint' => 'https://registry.example.test', 'credential' => 'secret']);
    $network = $action->execute(['team_id' => 'team-1', 'kind' => 'network', 'name' => 'frontend']);
    $volume = $action->execute(['team_id' => 'team-1', 'kind' => 'volume', 'name' => 'uploads']);
    $secret = $action->execute(['team_id' => 'team-1', 'kind' => 'secret', 'name' => 'APP_KEY', 'value' => 'hidden']);
    $limit = $action->execute(['team_id' => 'team-1', 'kind' => 'limit', 'workload_id' => 'workload-1', 'cpu_millis' => 500, 'memory_bytes' => 1048576]);
    $lifecycle = $action->execute(['team_id' => 'team-1', 'kind' => 'lifecycle', 'workload_id' => 'workload-1', 'operation' => 'start', 'status' => 'queued']);
    expect($image->repository)->toBe('registry/app')->and($registry->credential)->toBe('secret')->and($network->status)->toBe('active')->and($volume->status)->toBe('available')->and($secret->value)->toBe('hidden')->and($limit->cpu_millis)->toBe(500)->and($lifecycle->operation)->toBe('start');
});
it('rejects unknown container assets', function (): void {
    expect(fn () => app(RegisterContainerAsset::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
});
