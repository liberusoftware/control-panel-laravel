<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\OsAdapters\Actions\RecordOsResource;
use Liberu\ControlPanel\OsAdapters\Models\OsService;
use Liberu\ControlPanel\OsAdapters\OsAdaptersServiceProvider;
use Liberu\ControlPanel\OsAdaptersApi\OsAdaptersApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(OsAdaptersServiceProvider::class);
    app()->register(OsAdaptersApiServiceProvider::class);
    $this->artisan('migrate');
});

it('updates only a current-team OS service through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $record = app(RecordOsResource::class);
    $service = $record->execute(OsService::class, ['team_id' => $team->getKey(), 'node_id' => 'node-1', 'name' => 'nginx', 'status' => 'running']);
    $other = $record->execute(OsService::class, ['team_id' => $otherTeam->getKey(), 'node_id' => 'node-2', 'name' => 'httpd', 'status' => 'running']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/os-adapters/services/'.$other->getKey(), ['name' => 'nope'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/os-adapters/services/'.$service->getKey(), ['name' => 'nginx-mainline', 'enabled' => false])->assertOk()->assertJsonPath('data.attributes.name', 'nginx-mainline')->assertJsonPath('data.attributes.enabled', false);
});

it('reports tenant-scoped service status through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $record = app(RecordOsResource::class);
    $record->execute(OsService::class, ['team_id' => $team->getKey(), 'node_id' => 'node-1', 'name' => 'nginx', 'status' => 'running']);
    $record->execute(OsService::class, ['team_id' => $team->getKey(), 'node_id' => 'node-1', 'name' => 'redis', 'status' => 'stopped']);
    $record->execute(OsService::class, ['team_id' => $otherTeam->getKey(), 'node_id' => 'node-2', 'name' => 'mysql', 'status' => 'missing']);

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/control-panel/os-adapters/services/status')->assertOk()->assertJsonCount(2, 'services');
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/control-panel/os-adapters/services/missing')->assertOk()->assertJsonPath('count', 0);
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/control-panel/os-adapters/services/stopped')->assertOk()->assertJsonPath('count', 1);
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/control-panel/os-adapters/services/mysql/check')->assertNotFound();
});
