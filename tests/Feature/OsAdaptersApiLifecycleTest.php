<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\OsAdapters\Actions\CreateFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\RecordOsResource;
use Liberu\ControlPanel\OsAdapters\Models\FirewallRule;
use Liberu\ControlPanel\OsAdapters\Models\OsAdapter;
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

it('returns read-only installation commands for missing services on the current team', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    OsAdapter::query()->create([
        'team_id' => $team->getKey(), 'node_id' => 'node-1', 'operating_system' => 'AlmaLinux',
        'version' => '9', 'status' => 'available', 'capabilities' => [], 'metadata' => [],
    ]);
    app(RecordOsResource::class)->execute(OsService::class, ['team_id' => $team->getKey(), 'node_id' => 'node-1', 'name' => 'nginx', 'status' => 'missing']);
    app(RecordOsResource::class)->execute(OsService::class, ['team_id' => $team->getKey(), 'node_id' => 'node-1', 'name' => 'dovecot', 'status' => 'missing']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/os-adapters/services/install-commands')
        ->assertOk()
        ->assertJsonPath('operating_system', 'AlmaLinux')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('commands.0', 'sudo dnf install -y dovecot nginx');
});

it('manages only current-team firewall rules through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $rule = app(CreateFirewallRule::class)->execute(['team_id' => $team->getKey(), 'node_id' => 'node-1', 'direction' => 'inbound', 'action' => 'allow', 'source' => '10.0.0.0/8']);
    $other = app(CreateFirewallRule::class)->execute(['team_id' => $otherTeam->getKey(), 'node_id' => 'node-2', 'direction' => 'outbound', 'action' => 'deny', 'source' => '192.0.2.0/24']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/os-adapters/firewall-rules/'.$other->getKey(), ['action' => 'allow'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/os-adapters/firewall-rules/'.$rule->getKey(), ['action' => 'deny'])->assertOk()->assertJsonPath('data.attributes.action', 'deny');
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/os-adapters/firewall-rules/'.$rule->getKey())->assertNoContent();
    expect(FirewallRule::query()->find($rule->getKey()))->toBeNull();
});
