<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateRedirect;
use Liberu\ControlPanel\WebHosting\Actions\CreateVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\RequestCertificate;
use Liberu\ControlPanel\WebHosting\Enums\CertificateStatus;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\WebHostingServiceProvider;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(WebHostingServiceProvider::class);
    $this->artisan('migrate');
});

it('owns domain, virtual-host, certificate, and redirect lifecycle data', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'Example.COM.']);
    $host = app(CreateVirtualHost::class)->execute($domain, ['node_id' => 'node-1', 'server' => 'nginx', 'runtime' => 'php-8.5', 'document_root' => '/srv/example']);
    $certificate = app(RequestCertificate::class)->execute($domain);
    $redirect = app(CreateRedirect::class)->execute($domain, ['source' => '/old', 'destination' => '/new', 'status_code' => 308]);

    expect($domain->hostname)->toBe('example.com')
        ->and($domain->status)->toBe(DomainStatus::Pending)
        ->and($host->document_root)->toBe('/srv/example')
        ->and($certificate->status)->toBe(CertificateStatus::Pending)
        ->and($redirect->status_code)->toBe(308);
});

it('rejects invalid redirects and malformed domains', function (): void {
    expect(fn () => app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'not a hostname']))
        ->toThrow(ValidationException::class);

    $domain = Domain::query()->create(['team_id' => 'team-1', 'hostname' => 'example.test', 'status' => DomainStatus::Pending]);
    expect(fn () => app(CreateRedirect::class)->execute($domain, ['source' => '/old', 'destination' => '/new', 'status_code' => 200]))
        ->toThrow(ValidationException::class);
});
