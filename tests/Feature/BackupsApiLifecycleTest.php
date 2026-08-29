<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Backups\Actions\CreateDestination;
use Liberu\ControlPanel\Backups\Actions\CreatePolicy;
use Liberu\ControlPanel\Backups\Actions\CreateSchedule;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
use Liberu\ControlPanel\Backups\BackupsServiceProvider;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;
use Liberu\ControlPanel\BackupsApi\BackupsApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(BackupsServiceProvider::class);
    app()->register(BackupsApiServiceProvider::class);
    $this->artisan('migrate');
});

it('verifies and restores only a current-team snapshot through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $policy = app(CreatePolicy::class)->execute(['team_id' => $team->getKey(), 'name' => 'Nightly', 'storage_driver' => 'local']);
    $snapshot = app(CreateSnapshot::class)->execute($policy);
    $otherPolicy = app(CreatePolicy::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Other', 'storage_driver' => 'local']);
    $otherSnapshot = app(CreateSnapshot::class)->execute($otherPolicy);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/backups/snapshots/'.$snapshot->getKey().'/verify', ['checksum' => 'sha256:abc'])
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'verified');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/backups/snapshots/'.$snapshot->getKey().'/restore', ['target' => 'node-1'])
        ->assertAccepted()
        ->assertJsonPath('data.attributes.status', 'queued');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/backups/snapshots/'.$otherSnapshot->getKey().'/verify', ['checksum' => 'sha256:other'])
        ->assertNotFound();
});

it('updates and deletes only a current-team backup policy through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreatePolicy::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Foreign', 'storage_driver' => 'local']);
    $owned = app(CreatePolicy::class)->execute(['team_id' => $team->getKey(), 'name' => 'Owned', 'storage_driver' => 'local']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/backups/policies/'.$foreign->getKey(), ['name' => 'Blocked'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/backups/policies/'.$owned->getKey(), ['name' => 'Updated', 'retention_days' => 10])->assertOk()->assertJsonPath('data.attributes.name', 'Updated');
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/policies/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/policies/'.$owned->getKey())->assertNoContent();
});

it('updates and deletes only a current-team backup destination through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateDestination::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Foreign', 'driver' => 'local']);
    $owned = app(CreateDestination::class)->execute(['team_id' => $team->getKey(), 'name' => 'Owned', 'driver' => 'local']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/backups/destinations/'.$foreign->getKey(), ['name' => 'Blocked'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/backups/destinations/'.$owned->getKey(), ['name' => 'Updated', 'driver' => 's3'])->assertOk()->assertJsonPath('data.attributes.name', 'Updated');
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/destinations/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/destinations/'.$owned->getKey())->assertNoContent();
});

it('updates and deletes only a current-team backup schedule through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignPolicy = app(CreatePolicy::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Foreign', 'storage_driver' => 'local']);
    $ownedPolicy = app(CreatePolicy::class)->execute(['team_id' => $team->getKey(), 'name' => 'Owned', 'storage_driver' => 'local']);
    $foreign = app(CreateSchedule::class)->execute($foreignPolicy, '0 1 * * *');
    $owned = app(CreateSchedule::class)->execute($ownedPolicy, '0 2 * * *');

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/backups/schedules/'.$foreign->getKey(), ['cron' => '0 3 * * *'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/backups/schedules/'.$owned->getKey(), ['cron' => '0 4 * * *', 'timezone' => 'UTC'])->assertOk()->assertJsonPath('data.attributes.cron', '0 4 * * *');
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/schedules/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/schedules/'.$owned->getKey())->assertNoContent();
});

it('deletes only a current-team snapshot through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignPolicy = app(CreatePolicy::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Foreign', 'storage_driver' => 'local']);
    $ownedPolicy = app(CreatePolicy::class)->execute(['team_id' => $team->getKey(), 'name' => 'Owned', 'storage_driver' => 'local']);
    $foreign = app(CreateSnapshot::class)->execute($foreignPolicy);
    $owned = app(CreateSnapshot::class)->execute($ownedPolicy);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/snapshots/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/snapshots/'.$owned->getKey())->assertNoContent();

    expect($owned->newQuery()->whereKey($owned->getKey())->exists())->toBeFalse();

    $running = app(CreateSnapshot::class)->execute($ownedPolicy);
    $running->update(['status' => SnapshotStatus::Running]);
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/backups/snapshots/'.$running->getKey())->assertUnprocessable();
});
