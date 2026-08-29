<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Liberu\ControlPanel\Dns\Actions\ArchiveZone;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\Actions\SuspendZone;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\Dns\Models\DnsTemplate;
use Liberu\ControlPanel\DnsLivewire\Components\DnsTemplateInventory;
use Liberu\ControlPanel\DnsLivewire\Components\RecordInventory;
use Liberu\ControlPanel\DnsLivewire\Components\ZoneInventory;
use Liberu\ControlPanel\DnsLivewire\DnsLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

it('searches DNS zones by their domain field', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    app(CreateZone::class)->execute(['team_id' => $team->getKey(), 'domain' => 'example.test']);
    app(CreateZone::class)->execute(['team_id' => $team->getKey(), 'domain' => 'other.test']);

    $this->actingAs($user);
    $inventory = app(ZoneInventory::class);
    $inventory->search = 'example';

    expect($inventory->render()->getData()['zones']->total())->toBe(1);
});

it('creates records through the tenant-scoped Livewire inventory', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $zone = app(CreateZone::class)->execute(['team_id' => $team->getKey(), 'domain' => 'records.example.test']);

    $this->actingAs($user);
    $inventory = app(RecordInventory::class);
    $inventory->zoneId = $zone->getKey();
    $inventory->content = '192.0.2.20';
    $inventory->save(app(CreateRecord::class));

    expect($zone->records()->count())->toBe(1);
});

it('renders named DNS feature inventories only for the current team', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    DnsTemplate::query()->create(['id' => (string) Str::uuid(), 'team_id' => $team->getKey(), 'name' => 'Current template', 'records' => [], 'active' => true]);
    DnsTemplate::query()->create(['id' => (string) Str::uuid(), 'team_id' => $otherTeam->getKey(), 'name' => 'Other template', 'records' => [], 'active' => true]);

    $this->actingAs($user);
    $templates = app(DnsTemplateInventory::class)->render()->getData()['items'];

    expect($templates)->toHaveCount(1)->and($templates->first()->name)->toBe('Current template');
});

it('fails closed when a named DNS feature inventory has no current team', function (): void {
    $this->actingAs(User::factory()->create(['current_team_id' => null]));

    expect(fn () => app(DnsTemplateInventory::class)->render())
        ->toThrow(HttpException::class);
});
