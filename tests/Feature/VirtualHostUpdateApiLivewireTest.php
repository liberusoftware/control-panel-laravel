<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\DeleteVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\UpdateVirtualHost;
use Liberu\ControlPanel\WebHosting\Models\VirtualHost;
use Liberu\ControlPanel\WebHosting\WebHostingServiceProvider;
use Liberu\ControlPanel\WebHostingApi\WebHostingApiServiceProvider;
use Liberu\ControlPanel\WebHostingLivewire\Components\HostingResourceInventory;
use Liberu\ControlPanel\WebHostingLivewire\WebHostingLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(WebHostingServiceProvider::class);
    app()->register(WebHostingApiServiceProvider::class);
    app()->register(WebHostingLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('updates only a current-team virtual host through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignDomain = app(CreateDomain::class)->execute(['team_id' => $otherTeam->getKey(), 'hostname' => 'foreign.test']);
    $ownedDomain = app(CreateDomain::class)->execute(['team_id' => $team->getKey(), 'hostname' => 'owned.test']);
    $foreign = app(CreateVirtualHost::class)->execute($foreignDomain, ['node_id' => 'foreign-node', 'server' => 'nginx', 'document_root' => '/srv/foreign']);
    $owned = app(CreateVirtualHost::class)->execute($ownedDomain, ['node_id' => 'owned-node', 'server' => 'nginx', 'document_root' => '/srv/owned']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/web-hosting/virtual-hosts/'.$foreign->getKey(), ['document_root' => '/srv/nope'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/web-hosting/virtual-hosts/'.$owned->getKey(), ['document_root' => '/srv/updated'])->assertOk()->assertJsonPath('data.attributes.document_root', '/srv/updated');
});

it('updates only a current-team virtual host from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignDomain = app(CreateDomain::class)->execute(['team_id' => $otherTeam->getKey(), 'hostname' => 'foreign.test']);
    $ownedDomain = app(CreateDomain::class)->execute(['team_id' => $team->getKey(), 'hostname' => 'owned.test']);
    $foreign = app(CreateVirtualHost::class)->execute($foreignDomain, ['node_id' => 'foreign-node', 'server' => 'nginx', 'document_root' => '/srv/foreign']);
    $owned = app(CreateVirtualHost::class)->execute($ownedDomain, ['node_id' => 'owned-node', 'server' => 'nginx', 'document_root' => '/srv/owned']);
    $inventory = app(HostingResourceInventory::class);
    $this->actingAs($user);

    expect(fn () => $inventory->updateVirtualHost($foreign->getKey(), ['domain_id' => $foreignDomain->getKey(), 'server' => 'nginx', 'document_root' => '/srv/nope'], app(UpdateVirtualHost::class)))->toThrow(ModelNotFoundException::class);
    $inventory->updateVirtualHost($owned->getKey(), ['domain_id' => $ownedDomain->getKey(), 'server' => 'nginx', 'document_root' => '/srv/updated'], app(UpdateVirtualHost::class));

    expect(VirtualHost::query()->findOrFail($owned->getKey())->document_root)->toBe('/srv/updated');
});

it('deletes only a current-team virtual host through API and Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignDomain = app(CreateDomain::class)->execute(['team_id' => $otherTeam->getKey(), 'hostname' => 'foreign-delete.test']);
    $ownedDomain = app(CreateDomain::class)->execute(['team_id' => $team->getKey(), 'hostname' => 'owned-delete.test']);
    $foreign = app(CreateVirtualHost::class)->execute($foreignDomain, ['node_id' => 'foreign-node', 'server' => 'nginx', 'document_root' => '/srv/foreign']);
    $owned = app(CreateVirtualHost::class)->execute($ownedDomain, ['node_id' => 'owned-node', 'server' => 'nginx', 'document_root' => '/srv/owned']);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/web-hosting/virtual-hosts/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/web-hosting/virtual-hosts/'.$owned->getKey())->assertNoContent();

    $livewireHost = app(CreateVirtualHost::class)->execute($ownedDomain, ['node_id' => 'owned-node', 'server' => 'nginx', 'document_root' => '/srv/livewire']);
    $this->actingAs($user);
    app(HostingResourceInventory::class)->deleteVirtualHost($livewireHost->getKey(), app(DeleteVirtualHost::class));

    expect($owned->fresh())->toBeNull()->and($livewireHost->fresh())->toBeNull();
});
