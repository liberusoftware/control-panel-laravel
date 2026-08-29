<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\UpdateHostedApplication;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
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

it('updates only a current-team hosted application through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignDomain = app(CreateDomain::class)->execute(['team_id' => $otherTeam->getKey(), 'hostname' => 'foreign.test']);
    $ownedDomain = app(CreateDomain::class)->execute(['team_id' => $team->getKey(), 'hostname' => 'owned.test']);
    $foreign = HostedApplication::query()->create(['team_id' => $otherTeam->getKey(), 'domain_id' => $foreignDomain->getKey(), 'name' => 'Foreign', 'type' => 'static', 'document_root' => '/srv/foreign', 'status' => 'installed']);
    $owned = HostedApplication::query()->create(['team_id' => $team->getKey(), 'domain_id' => $ownedDomain->getKey(), 'name' => 'Owned', 'type' => 'static', 'document_root' => '/srv/owned', 'status' => 'installed']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/web-hosting/applications/'.$foreign->getKey(), ['name' => 'Nope'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/web-hosting/applications/'.$owned->getKey(), ['name' => 'Updated'])->assertOk()->assertJsonPath('data.attributes.name', 'Updated');
});

it('updates only a current-team hosted application from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignDomain = app(CreateDomain::class)->execute(['team_id' => $otherTeam->getKey(), 'hostname' => 'foreign.test']);
    $ownedDomain = app(CreateDomain::class)->execute(['team_id' => $team->getKey(), 'hostname' => 'owned.test']);
    $foreign = HostedApplication::query()->create(['team_id' => $otherTeam->getKey(), 'domain_id' => $foreignDomain->getKey(), 'name' => 'Foreign', 'type' => 'static', 'document_root' => '/srv/foreign', 'status' => 'installed']);
    $owned = HostedApplication::query()->create(['team_id' => $team->getKey(), 'domain_id' => $ownedDomain->getKey(), 'name' => 'Owned', 'type' => 'static', 'document_root' => '/srv/owned', 'status' => 'installed']);
    $inventory = app(HostingResourceInventory::class);
    $this->actingAs($user);

    expect(fn () => $inventory->updateApplication($foreign->getKey(), ['domain_id' => $foreignDomain->getKey(), 'name' => 'Nope', 'type' => 'static', 'document_root' => '/srv/nope'], app(UpdateHostedApplication::class)))->toThrow(ModelNotFoundException::class);
    $inventory->updateApplication($owned->getKey(), ['domain_id' => $ownedDomain->getKey(), 'name' => 'Updated', 'type' => 'static', 'document_root' => '/srv/updated'], app(UpdateHostedApplication::class));

    expect($owned->refresh()->name)->toBe('Updated');
});
