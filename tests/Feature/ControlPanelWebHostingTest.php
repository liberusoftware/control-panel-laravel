<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Actions\ActivateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateVirtualHost;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHosting\Events\DomainCreated;
use Liberu\ControlPanel\WebHosting\WebHostingServiceProvider;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(WebHostingServiceProvider::class);
    $this->artisan('migrate');
});

it('normalizes and creates a pending domain', function (): void {
    Event::fake();

    $domain = app(CreateDomain::class)->execute([
        'team_id' => 'team-1',
        'hostname' => 'Example.COM.',
    ]);

    expect($domain->hostname)->toBe('example.com')
        ->and($domain->status)->toBe(DomainStatus::Pending);
    Event::assertDispatched(DomainCreated::class);
});

it('activates a pending domain', function (): void {
    $domain = app(CreateDomain::class)->execute(['hostname' => 'example.test']);

    expect(app(ActivateDomain::class)->execute($domain)->status)->toBe(DomainStatus::Active);
});

it('rejects invalid hostnames', function (): void {
    expect(fn () => app(CreateDomain::class)->execute(['hostname' => 'not a hostname']))
        ->toThrow(ValidationException::class);
});

it('creates a desired virtual host for a domain and node', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'example.test']);

    $host = app(CreateVirtualHost::class)->execute($domain, ['node_id' => 'a3f6f5a4-1c5f-4e83-bb15-b7d7d6f8db11', 'server' => 'nginx', 'runtime' => 'php-8.5', 'document_root' => '/srv/example.test/public']);

    expect($host->domain_id)->toBe($domain->getKey())->and($host->document_root)->toBe('/srv/example.test/public')->and($host->active)->toBeTrue();
});
