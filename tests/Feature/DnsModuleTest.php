<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Actions\ArchiveZone;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\Actions\RecordDnsCheck;
use Liberu\ControlPanel\Dns\Actions\RegisterDnsFeature;
use Liberu\ControlPanel\Dns\Actions\SuspendZone;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\Dns\Models\Zone;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(DnsServiceProvider::class);
    $this->artisan('migrate');
});
it('supports DNS templates, DNSSEC, providers, validation, and propagation checks', function (): void {
    $a = app(RegisterDnsFeature::class);
    $template = $a->execute(['team_id' => 'team-1', 'kind' => 'template', 'name' => 'web', 'records' => [['type' => 'A', 'content' => '192.0.2.1']]]);
    $dnssec = $a->execute(['team_id' => 'team-1', 'kind' => 'dnssec', 'key_tag' => 1, 'algorithm' => 13, 'digest_type' => 2, 'digest' => 'abc']);
    $provider = $a->execute(['team_id' => 'team-1', 'kind' => 'provider', 'name' => 'cloud', 'driver' => 'cloudflare', 'credentials' => ['token' => 'secret']]);
    $validation = $a->execute(['team_id' => 'team-1', 'kind' => 'validation', 'status' => 'passed', 'resolver' => '1.1.1.1', 'expected' => ['A' => '192.0.2.1'], 'observed' => ['A' => '192.0.2.1']]);
    $propagation = $a->execute(['team_id' => 'team-1', 'kind' => 'propagation', 'status' => 'passed', 'nameservers' => ['ns1.example.test'], 'results' => ['ns1.example.test' => true]]);
    expect($template->name)->toBe('web')->and($dnssec->digest)->toBe('abc')->and($provider->credentials)->toMatchArray(['token' => 'secret'])->and($validation->status)->toBe('passed')->and($propagation->status)->toBe('passed');
});
it('rejects unsupported DNS features', function (): void {
    expect(fn () => app(RegisterDnsFeature::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
});

it('requires tenant context and scopes related DNS resources', function (): void {
    expect(fn () => app(RegisterDnsFeature::class)->execute(['kind' => 'template', 'name' => 'orphan']))
        ->toThrow(ValidationException::class);

    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'owned.example.test']);

    expect(fn () => app(RegisterDnsFeature::class)->execute([
        'team_id' => 'team-2', 'kind' => 'dnssec', 'zone_id' => $zone->getKey(), 'digest' => 'abc',
    ]))->toThrow(HttpException::class);

    expect(Zone::query()->where('team_id', 'team-1')->count())->toBe(1);
});

it('suspends and archives a DNS zone with terminal-state validation', function (): void {
    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'provider' => 'cloud']);

    expect(app(SuspendZone::class)->execute($zone)->status->value)->toBe('suspended');
    expect(app(ArchiveZone::class)->execute($zone->refresh())->status->value)->toBe('archived')
        ->and(fn () => app(ArchiveZone::class)->execute($zone->refresh()))
        ->toThrow(ValidationException::class);
});

it('requires tenant context and scopes recorded DNS checks to the tenant zone', function (): void {
    $zone = app(CreateZone::class)->execute(['team_id' => 'team-1', 'domain' => 'checks.example.test']);

    expect(fn () => app(RecordDnsCheck::class)->execute(['zone_id' => $zone->getKey(), 'kind' => 'validation']))
        ->toThrow(ValidationException::class);
    expect(fn () => app(RecordDnsCheck::class)->execute(['team_id' => 'team-2', 'zone_id' => $zone->getKey(), 'kind' => 'validation']))
        ->toThrow(HttpException::class);

    $check = app(RecordDnsCheck::class)->execute(['team_id' => 'team-1', 'zone_id' => $zone->getKey(), 'kind' => 'validation', 'status' => 'passed']);
    expect($check->team_id)->toBe('team-1')->and($check->zone_id)->toBe($zone->getKey());
});
