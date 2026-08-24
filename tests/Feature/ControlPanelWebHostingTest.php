<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Actions\ActivateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
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
