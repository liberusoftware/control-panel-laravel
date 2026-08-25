<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Backups\Actions\CreatePolicy;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
use Liberu\ControlPanel\Backups\Actions\RequestRestore;
use Liberu\ControlPanel\Backups\Actions\VerifySnapshot;
use Liberu\ControlPanel\Backups\BackupsServiceProvider;
use Liberu\ControlPanel\Backups\Enums\RestoreStatus;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;
use Liberu\ControlPanel\Backups\Models\BackupRestore;
use Liberu\ControlPanel\BackupsLivewire\BackupsLivewireServiceProvider;
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
