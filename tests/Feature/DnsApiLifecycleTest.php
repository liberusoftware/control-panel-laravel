<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\DnsApi\DnsApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(DnsServiceProvider::class);
    app()->register(DnsApiServiceProvider::class);
    $this->artisan('migrate');
});

it('suspends and archives only a current-team DNS zone through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $zone = app(CreateZone::class)->execute(['team_id' => $team->getKey(), 'domain' => 'example.test', 'provider' => 'cloud']);
    $otherZone = app(CreateZone::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'other.test', 'provider' => 'cloud']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/dns/zones/'.$zone->getKey().'/suspend')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'suspended');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/dns/zones/'.$zone->getKey().'/archive')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'archived');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/dns/zones/'.$otherZone->getKey().'/archive')
        ->assertNotFound();
});
