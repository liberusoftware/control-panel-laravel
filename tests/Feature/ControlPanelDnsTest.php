<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Actions\ActivateZone;
use Liberu\ControlPanel\Dns\Actions\CheckDnsPropagation;
use Liberu\ControlPanel\Dns\Actions\CheckDnsResolution;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\Actions\DeleteRecord;
use Liberu\ControlPanel\Dns\Actions\UpdateRecord;
use Liberu\ControlPanel\Dns\Actions\UpdateZone;
use Liberu\ControlPanel\Dns\Actions\ValidateRecord;
use Liberu\ControlPanel\Dns\Contracts\DnsResolver;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\Dns\Enums\ZoneStatus;
use Liberu\ControlPanel\Dns\Events\ZoneCreated;
use Liberu\ControlPanel\Dns\Models\DnsValidation;
use Liberu\ControlPanel\Dns\Models\PropagationCheck;

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

it('updates a DNS record through the domain action', function (): void {
    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test']);
    $record = app(CreateRecord::class)->execute(['team_id' => 'team-1', 'zone_id' => $zone->getKey(), 'name' => '@', 'type' => 'A', 'content' => '192.0.2.1']);

    $updated = app(UpdateRecord::class)->execute($record, ['content' => '192.0.2.2', 'ttl' => 7200]);

    expect($updated->content)->toBe('192.0.2.2')->and($updated->ttl)->toBe(7200);
});

it('updates a DNS zone through the domain action', function (): void {
    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test']);

    $updated = app(UpdateZone::class)->execute($zone, ['domain' => 'updated.test', 'provider' => 'cloud', 'dnssec_enabled' => true]);

    expect($updated->domain)->toBe('updated.test')->and($updated->provider)->toBe('cloud')->and($updated->dnssec_enabled)->toBeTrue();
});

it('deletes a DNS record through the domain action', function (): void {
    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test']);
    $record = app(CreateRecord::class)->execute(['team_id' => 'team-1', 'zone_id' => $zone->getKey(), 'name' => '@', 'type' => 'A', 'content' => '192.0.2.1']);

    app(DeleteRecord::class)->execute($record);

    expect($zone->records()->whereKey($record->getKey())->exists())->toBeFalse();
});

it('validates DNS record values by type', function (): void {
    expect(app(ValidateRecord::class)->execute(['record_type' => 'A', 'name' => '@', 'value' => '192.0.2.1']))->toMatchArray(['valid' => true]);
    expect(fn () => app(ValidateRecord::class)->execute(['record_type' => 'A', 'name' => '@', 'value' => 'not-an-ip']))->toThrow(ValidationException::class);
    expect(fn () => app(ValidateRecord::class)->execute(['record_type' => 'SRV', 'name' => '_http', 'value' => '0 5 443 service.example.com']))->toThrow(ValidationException::class);
});

it('checks DNS resolution and propagation through the resolver boundary', function (): void {
    app()->instance(DnsResolver::class, new class() implements DnsResolver
    {
        public function records(string $hostname, string $recordType): array
        {
            return [['host' => $hostname, 'ip' => '192.0.2.1']];
        }

        public function nameservers(string $hostname): array
        {
            return ['ns1.example.test', 'ns2.example.test'];
        }
    });
    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test']);
    $record = app(CreateRecord::class)->execute(['team_id' => 'team-1', 'zone_id' => $zone->getKey(), 'name' => '@', 'type' => 'A', 'content' => '192.0.2.1']);

    $resolution = app(CheckDnsResolution::class)->execute(['team_id' => 'team-1', 'zone_id' => $zone->getKey(), 'record_id' => $record->getKey()]);
    $propagation = app(CheckDnsPropagation::class)->execute(['team_id' => 'team-1', 'zone_id' => $zone->getKey()]);

    expect($resolution['success'])->toBeTrue()
        ->and($resolution['validation'])->toBeInstanceOf(DnsValidation::class)
        ->and($resolution['validation']->status)->toBe('passed')
        ->and($propagation['success'])->toBeTrue()
        ->and($propagation['propagation'])->toBeInstanceOf(PropagationCheck::class)
        ->and($propagation['propagation']->nameservers)->toHaveCount(2);
});

it('rejects DNS checks for a zone outside the tenant', function (): void {
    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test']);

    expect(fn () => app(CheckDnsResolution::class)->execute(['team_id' => 'team-2', 'zone_id' => $zone->getKey()]))
        ->toThrow(ModelNotFoundException::class);
});
