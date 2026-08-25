<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Dns\Actions\ArchiveZone;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\Actions\SuspendZone;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\DnsLivewire\Components\ZoneInventory;
use Liberu\ControlPanel\DnsLivewire\DnsLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(DnsServiceProvider::class);
    app()->register(DnsLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('suspends and archives only a current-team DNS zone from Livewire', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $zone = app(CreateZone::class)->execute(['team_id' => $team->getKey(), 'domain' => 'example.test', 'provider' => 'cloud']);

    $this->actingAs($user);
    $inventory = app(ZoneInventory::class);
    $inventory->suspend($zone->getKey(), app(SuspendZone::class));
    $inventory->archive($zone->getKey(), app(ArchiveZone::class));

    expect($zone->refresh()->status->value)->toBe('archived');
});
