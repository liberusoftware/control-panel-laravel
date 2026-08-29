<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Actions\DeleteWorkload;
use Liberu\ControlPanel\Containers\Actions\RegisterWorkload;
use Liberu\ControlPanel\Containers\Actions\StartWorkload;
use Liberu\ControlPanel\Containers\Actions\StopWorkload;
use Liberu\ControlPanel\Containers\ContainersServiceProvider;
use Liberu\ControlPanel\Containers\Models\Workload;
use Liberu\ControlPanel\ContainersApi\ContainersApiServiceProvider;
use Liberu\ControlPanel\ContainersLivewire\Components\WorkloadInventory;
use Liberu\ControlPanel\ContainersLivewire\ContainersLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(ContainersServiceProvider::class);
    app()->register(ContainersApiServiceProvider::class);
    app()->register(ContainersLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('starts and stops a workload and rejects duplicate transitions', function (): void {
    $workload = app(RegisterWorkload::class)->execute(['team_id' => 'team-1', 'node_id' => (string) Str::uuid(), 'name' => 'web', 'image' => 'nginx']);
    $running = app(StartWorkload::class)->execute($workload);
    expect($running->status)->toBe('running');
    expect(fn () => app(StartWorkload::class)->execute($running))->toThrow(ValidationException::class);
    $stopped = app(StopWorkload::class)->execute($running);
    expect($stopped->status)->toBe('stopped');

    app(DeleteWorkload::class)->execute($stopped);
    expect(Workload::query()->whereKey($workload->getKey())->exists())->toBeFalse();
});

it('enforces tenant ownership through API and Livewire workload controls', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $workload = app(RegisterWorkload::class)->execute(['team_id' => $team->getKey(), 'node_id' => (string) Str::uuid(), 'name' => 'web', 'image' => 'nginx']);
    $otherWorkload = app(RegisterWorkload::class)->execute(['team_id' => $otherTeam->getKey(), 'node_id' => (string) Str::uuid(), 'name' => 'other', 'image' => 'nginx']);

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/containers/'.$workload->getKey().'/start')->assertOk()->assertJsonPath('data.attributes.status', 'running');
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/containers/'.$otherWorkload->getKey().'/stop')->assertNotFound();

    $livewireWorkload = app(RegisterWorkload::class)->execute(['team_id' => $team->getKey(), 'node_id' => (string) Str::uuid(), 'name' => 'livewire', 'image' => 'nginx']);
    $this->actingAs($user);
    app(WorkloadInventory::class)->start($livewireWorkload->getKey(), app(StartWorkload::class));
    expect(Workload::query()->findOrFail($livewireWorkload->getKey())->status)->toBe('running');
});

it('deletes only stopped workloads through API and Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(RegisterWorkload::class)->execute(['team_id' => $otherTeam->getKey(), 'node_id' => (string) Str::uuid(), 'name' => 'foreign', 'image' => 'nginx']);
    $owned = app(RegisterWorkload::class)->execute(['team_id' => $team->getKey(), 'node_id' => (string) Str::uuid(), 'name' => 'owned', 'image' => 'nginx']);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/containers/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/containers/'.$owned->getKey())->assertNoContent();

    $running = app(RegisterWorkload::class)->execute(['team_id' => $team->getKey(), 'node_id' => (string) Str::uuid(), 'name' => 'running', 'image' => 'nginx']);
    app(StartWorkload::class)->execute($running);
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/containers/'.$running->getKey())->assertUnprocessable();
});
