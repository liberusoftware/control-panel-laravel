<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
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

it('updates only a current-team DNS record through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $ownedZone = app(CreateZone::class)->execute(['team_id' => $team->getKey(), 'domain' => 'owned.test']);
    $foreignZone = app(CreateZone::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'foreign.test']);
    $owned = app(CreateRecord::class)->execute(['team_id' => $team->getKey(), 'zone_id' => $ownedZone->getKey(), 'type' => 'A', 'content' => '192.0.2.1']);
    $foreign = app(CreateRecord::class)->execute(['team_id' => $otherTeam->getKey(), 'zone_id' => $foreignZone->getKey(), 'type' => 'A', 'content' => '192.0.2.2']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/dns/records/'.$foreign->getKey(), ['content' => '192.0.2.3'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/dns/records/'.$owned->getKey(), ['content' => '192.0.2.3'])->assertOk()->assertJsonPath('data.attributes.content', '192.0.2.3');
});
