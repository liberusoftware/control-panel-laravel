<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Backups\Actions\CreatePolicy;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
use Liberu\ControlPanel\Backups\BackupsServiceProvider;
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
