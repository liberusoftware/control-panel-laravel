<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Actions\RegisterContainerAsset;
use Liberu\ControlPanel\Containers\Actions\RegisterWorkload;
use Liberu\ControlPanel\Containers\ContainersServiceProvider;
use Liberu\ControlPanel\Containers\Models\ContainerSecret;
use Liberu\ControlPanel\ContainersApi\ContainersApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(ContainersServiceProvider::class);
    app()->register(ContainersApiServiceProvider::class);
    $this->artisan('migrate');
});
it('supports images, registries, networks, volumes, secrets, limits, and lifecycle', function (): void {
    $action = app(RegisterContainerAsset::class);
    $image = $action->execute(['team_id' => 'team-1', 'kind' => 'image', 'repository' => 'registry/app', 'tag' => 'v1']);
    $registry = $action->execute(['team_id' => 'team-1', 'kind' => 'registry', 'name' => 'private', 'endpoint' => 'https://registry.example.test', 'credential' => 'secret']);
    $network = $action->execute(['team_id' => 'team-1', 'kind' => 'network', 'name' => 'frontend']);
    $volume = $action->execute(['team_id' => 'team-1', 'kind' => 'volume', 'name' => 'uploads']);
    $secret = $action->execute(['team_id' => 'team-1', 'kind' => 'secret', 'name' => 'APP_KEY', 'value' => 'hidden']);
    $workload = app(RegisterWorkload::class)->execute(['team_id' => 'team-1', 'node_id' => (string) Str::uuid(), 'name' => 'web', 'image' => 'nginx']);
    $limit = $action->execute(['team_id' => 'team-1', 'kind' => 'limit', 'workload_id' => $workload->getKey(), 'cpu_millis' => 500, 'memory_bytes' => 1048576]);
    $lifecycle = $action->execute(['team_id' => 'team-1', 'kind' => 'lifecycle', 'workload_id' => $workload->getKey(), 'operation' => 'start', 'status' => 'queued']);
    expect($image->repository)->toBe('registry/app')->and($registry->credential)->toBe('secret')->and($network->status)->toBe('active')->and($volume->status)->toBe('available')->and($secret->value)->toBe('hidden')->and($limit->cpu_millis)->toBe(500)->and($lifecycle->operation)->toBe('start');
});
it('rejects unknown container assets', function (): void {
    expect(fn () => app(RegisterContainerAsset::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
});

it('lists container assets for the current team without exposing secret values', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $action = app(RegisterContainerAsset::class);

    $owned = $action->execute(['team_id' => $team->getKey(), 'kind' => 'secret', 'name' => 'owned', 'value' => 'private']);
    $action->execute(['team_id' => $otherTeam->getKey(), 'kind' => 'secret', 'name' => 'foreign', 'value' => 'private']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/containers/assets?kind=secret')
        ->assertOk()
        ->assertJsonPath('meta.kind', 'secret')
        ->assertJsonPath('data.0.attributes.name', 'owned')
        ->assertJsonMissingPath('data.0.attributes.value')
        ->assertJsonMissing(['name' => 'foreign']);

    expect(ContainerSecret::query()->where('team_id', $otherTeam->getKey())->count())->toBe(1);
});
