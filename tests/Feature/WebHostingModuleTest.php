<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateMimeType;
use Liberu\ControlPanel\WebHosting\Actions\CreateRedirect;
use Liberu\ControlPanel\WebHosting\Actions\CreateVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\DeleteHostedApplication;
use Liberu\ControlPanel\WebHosting\Actions\DeleteRedirect;
use Liberu\ControlPanel\WebHosting\Actions\DeleteVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\RecordResourceUsage;
use Liberu\ControlPanel\WebHosting\Actions\RequestCertificate;
use Liberu\ControlPanel\WebHosting\Actions\UpdateRedirect;
use Liberu\ControlPanel\WebHosting\Enums\CertificateStatus;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHosting\Queries\ListResourceUsage;
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

it('supports redirect policies and tenant-owned MIME type metadata', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'policy.example.test']);
    $redirect = app(CreateRedirect::class)->execute($domain, [
        'source' => '/legacy/(.*)', 'destination' => '/new/$1', 'status_code' => 301,
        'match_query_string' => true, 'is_regex' => true, 'priority' => 10,
    ]);
    $updated = app(UpdateRedirect::class)->execute($redirect, ['status_code' => 308, 'active' => false]);
    $mimeType = app(CreateMimeType::class)->execute($domain, ['extension' => '.webp', 'mime_type' => 'image/webp']);

    expect($updated->status_code)->toBe(308)
        ->and($updated->redirect_type)->toBe('308')
        ->and($updated->is_regex)->toBeTrue()
        ->and($updated->active)->toBeFalse()
        ->and($mimeType->domain->is($domain))->toBeTrue()
        ->and($mimeType->extension)->toBe('.webp');

    app(DeleteRedirect::class)->execute($updated);
    expect($updated->fresh())->toBeNull();
});

it('deletes hosting resources through domain actions', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'delete.example.test']);
    $virtualHost = app(CreateVirtualHost::class)->execute($domain, ['node_id' => 'node-1', 'server' => 'nginx', 'runtime' => 'php-8.5', 'document_root' => '/srv/delete']);
    $application = HostedApplication::query()->create(['team_id' => 'team-1', 'domain_id' => $domain->getKey(), 'name' => 'Delete me', 'type' => 'static', 'document_root' => '/srv/delete', 'status' => 'installed']);

    app(DeleteVirtualHost::class)->execute($virtualHost);
    app(DeleteHostedApplication::class)->execute($application);

    expect($virtualHost->fresh())->toBeNull()->and($application->fresh())->toBeNull();
});

it('records and lists tenant-scoped monthly resource usage', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'usage.example.test']);
    $foreignDomain = app(CreateDomain::class)->execute(['team_id' => 'team-2', 'hostname' => 'foreign-usage.example.test']);

    $usage = app(RecordResourceUsage::class)->execute($domain, [
        'month' => 8,
        'year' => 2026,
        'disk_usage_mb' => 500,
        'bandwidth_usage_mb' => 1024,
    ]);
    $updatedUsage = app(RecordResourceUsage::class)->execute($domain, [
        'month' => 8,
        'year' => 2026,
        'disk_usage_mb' => 550,
        'bandwidth_usage_mb' => 1200,
    ]);
    app(RecordResourceUsage::class)->execute($domain, [
        'month' => 7,
        'year' => 2026,
        'disk_usage_mb' => 400,
        'bandwidth_usage_mb' => 800,
    ]);
    app(RecordResourceUsage::class)->execute($foreignDomain, [
        'month' => 8,
        'year' => 2026,
        'disk_usage_mb' => 900,
        'bandwidth_usage_mb' => 1800,
    ]);

    expect($usage->domain->is($domain))
        ->toBeTrue()
        ->and($updatedUsage->getKey())->toBe($usage->getKey())
        ->and($updatedUsage->disk_usage_mb)->toBe(550)
        ->and(app(ListResourceUsage::class)->execute('team-1')->pluck('domain_id')->unique()->all())
        ->toBe([$domain->getKey()])
        ->and($domain->resourceUsage()->forMonth(8, 2026)->first()->bandwidth_usage_mb)
        ->toBe(1200);

    expect(fn () => app(RecordResourceUsage::class)->execute($domain, ['team_id' => 'team-2']))
        ->toThrow(ValidationException::class);
});
