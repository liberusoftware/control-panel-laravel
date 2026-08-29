<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Backups\Actions\CreateDestination;
use Liberu\ControlPanel\Backups\Actions\CreatePolicy;
use Liberu\ControlPanel\Backups\Actions\CreateSchedule;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
use Liberu\ControlPanel\Backups\Actions\DeleteDestination;
use Liberu\ControlPanel\Backups\Actions\DeletePolicy;
use Liberu\ControlPanel\Backups\Actions\DeleteSchedule;
use Liberu\ControlPanel\Backups\Actions\RequestRestore;
use Liberu\ControlPanel\Backups\Actions\UpdateDestination;
use Liberu\ControlPanel\Backups\Actions\UpdatePolicy;
use Liberu\ControlPanel\Backups\Actions\UpdateSchedule;
use Liberu\ControlPanel\Backups\Actions\VerifySnapshot;
use Liberu\ControlPanel\Backups\BackupsServiceProvider;
use Liberu\ControlPanel\Backups\Enums\RestoreStatus;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;
use Liberu\ControlPanel\Backups\Models\BackupRestore;
use Liberu\ControlPanel\BackupsLivewire\BackupsLivewireServiceProvider;
use Liberu\ControlPanel\BackupsLivewire\Components\DestinationInventory;
use Liberu\ControlPanel\BackupsLivewire\Components\PolicyInventory;
use Liberu\ControlPanel\BackupsLivewire\Components\ScheduleInventory;
use Liberu\ControlPanel\BackupsLivewire\Components\SnapshotInventory;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(BackupsServiceProvider::class);
    app()->register(BackupsLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('verifies and restores only a current-team snapshot from Livewire', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $policy = app(CreatePolicy::class)->execute(['team_id' => $team->getKey(), 'name' => 'Nightly', 'storage_driver' => 'local']);
    $snapshot = app(CreateSnapshot::class)->execute($policy);

    $this->actingAs($user);
    $inventory = app(SnapshotInventory::class);
    $inventory->checksum = 'sha256:abc';
    $inventory->verify($snapshot->getKey(), app(VerifySnapshot::class));
    expect($snapshot->refresh()->status)->toBe(SnapshotStatus::Verified);

    $inventory->restoreTarget = 'node-1';
    $inventory->restore($snapshot->getKey(), app(RequestRestore::class));
    expect(BackupRestore::query()->where('snapshot_id', $snapshot->getKey())->firstOrFail()->status)->toBe(RestoreStatus::Queued);
});

it('updates and deletes only a current-team backup policy from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreatePolicy::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Foreign', 'storage_driver' => 'local']);
    $owned = app(CreatePolicy::class)->execute(['team_id' => $team->getKey(), 'name' => 'Owned', 'storage_driver' => 'local']);

    $this->actingAs($user);
    $inventory = app(PolicyInventory::class);
    expect(fn () => $inventory->updatePolicy($foreign->getKey(), ['name' => 'Blocked', 'storage_driver' => 'local', 'retention_days' => 5], app(UpdatePolicy::class)))->toThrow(ModelNotFoundException::class);
    $inventory->updatePolicy($owned->getKey(), ['name' => 'Updated', 'storage_driver' => 's3', 'retention_days' => 10], app(UpdatePolicy::class));
    $inventory->deletePolicy($owned->getKey(), app(DeletePolicy::class));

    expect($owned->newQuery()->whereKey($owned->getKey())->exists())->toBeFalse();
});

it('updates and deletes only a current-team backup destination from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateDestination::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Foreign', 'driver' => 'local']);
    $owned = app(CreateDestination::class)->execute(['team_id' => $team->getKey(), 'name' => 'Owned', 'driver' => 'local']);

    $this->actingAs($user);
    $inventory = app(DestinationInventory::class);
    expect(fn () => $inventory->updateDestination($foreign->getKey(), ['name' => 'Blocked', 'driver' => 'local', 'retention_days' => 5], app(UpdateDestination::class)))->toThrow(ModelNotFoundException::class);
    $inventory->updateDestination($owned->getKey(), ['name' => 'Updated', 'driver' => 's3', 'retention_days' => 10], app(UpdateDestination::class));
    $inventory->deleteDestination($owned->getKey(), app(DeleteDestination::class));

    expect($owned->newQuery()->whereKey($owned->getKey())->exists())->toBeFalse();
});

it('updates and deletes only a current-team backup schedule from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignPolicy = app(CreatePolicy::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Foreign', 'storage_driver' => 'local']);
    $ownedPolicy = app(CreatePolicy::class)->execute(['team_id' => $team->getKey(), 'name' => 'Owned', 'storage_driver' => 'local']);
    $foreign = app(CreateSchedule::class)->execute($foreignPolicy, '0 1 * * *');
    $owned = app(CreateSchedule::class)->execute($ownedPolicy, '0 2 * * *');

    $this->actingAs($user);
    $inventory = app(ScheduleInventory::class);
    expect(fn () => $inventory->updateSchedule($foreign->getKey(), ['cron' => '0 3 * * *', 'timezone' => 'UTC'], app(UpdateSchedule::class)))->toThrow(ModelNotFoundException::class);
    $inventory->updateSchedule($owned->getKey(), ['cron' => '0 4 * * *', 'timezone' => 'UTC'], app(UpdateSchedule::class));
    $inventory->deleteSchedule($owned->getKey(), app(DeleteSchedule::class));

    expect($owned->newQuery()->whereKey($owned->getKey())->exists())->toBeFalse();
});
