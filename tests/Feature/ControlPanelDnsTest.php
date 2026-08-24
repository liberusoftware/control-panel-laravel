<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Actions\ActivateZone;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\Dns\Enums\ZoneStatus;
use Liberu\ControlPanel\Dns\Events\ZoneCreated;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(DnsServiceProvider::class);
    $this->artisan('migrate');
});

it('normalizes a domain and creates a draft zone', function (): void {
    Event::fake();
    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'Example.COM']);

    expect($zone->domain)->toBe('example.com')->and($zone->status)->toBe(ZoneStatus::Draft);
    Event::assertDispatched(ZoneCreated::class);
});

it('activates a zone', function (): void {
    $zone = app(CreateZone::class)->execute(['domain' => 'example.com']);

    expect(app(ActivateZone::class)->execute($zone)->status)->toBe(ZoneStatus::Active);
});

it('rejects invalid domains and archived activation', function (): void {
    expect(fn () => app(CreateZone::class)->execute(['domain' => 'not a domain']))->toThrow(ValidationException::class);
    $zone = app(CreateZone::class)->execute(['domain' => 'example.com']);
    $zone->update(['status' => ZoneStatus::Archived]);

    expect(fn () => app(ActivateZone::class)->execute($zone))->toThrow(ValidationException::class);
});
