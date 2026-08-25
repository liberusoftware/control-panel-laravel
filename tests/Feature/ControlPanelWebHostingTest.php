<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Actions\ActivateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\RegisterGitDeployment;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHosting\Events\DomainCreated;
use Liberu\ControlPanel\WebHosting\WebHostingServiceProvider;
use Liberu\ControlPanel\WebHosting\Models\GitDeployment;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHosting\Actions\SavePhpConfiguration;

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

it('restores Git deployment metadata, secret protection, and repository helpers', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'deploy.test']);
    $deployment = app(RegisterGitDeployment::class)->execute($domain, [
        'repository_url' => 'https://github.com/example/project.git',
        'deploy_path' => '/srv/deploy.test', 'deploy_key' => 'private-key', 'auto_deploy' => true,
    ]);

    expect($deployment->repository_type)->toBe('github')
        ->and($deployment->repository_name)->toBe('project')
        ->and($deployment->full_path)->toBe('/srv/deploy.test')
        ->and($deployment->isPrivate())->toBeTrue()
        ->and($deployment->usesOAuth())->toBeFalse()
        ->and($deployment->toArray())->not->toHaveKeys(['deploy_key', 'webhook_secret'])
        ->and(GitDeployment::validateGitHubWebhook('{"ref":"main"}', 'invalid', 'secret'))->toBeFalse();
});

it('rejects unsafe Git deployment configuration', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'invalid-deploy.test']);

    expect(fn () => app(RegisterGitDeployment::class)->execute($domain, [
        'repository_url' => 'not-a-url', 'deploy_path' => 'relative/path',
    ]))->toThrow(ValidationException::class);
});

it('preserves hosted application lifecycle helpers and encrypts configuration', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'app.test']);
    $application = HostedApplication::query()->create([
        'team_id' => 'team-1', 'domain_id' => $domain->getKey(), 'name' => 'Laravel app', 'type' => 'laravel',
        'document_root' => '/srv/app/', 'status' => 'installed', 'config' => ['admin_password' => 'not-plain-text'],
    ]);

    expect($application->isInstalled())->toBeTrue()
        ->and($application->getFullPathAttribute())->toBe('/srv/app')
        ->and(DB::table('control_panel_hosted_applications')->whereKey($application->getKey())->value('config'))->not->toBe('{"admin_password":"not-plain-text"}');
});

it('restores per-domain PHP configuration and renders safe INI directives', function (): void {
    $domain = app(CreateDomain::class)->execute(['team_id' => 'team-1', 'hostname' => 'php.test']);
    $configuration = app(SavePhpConfiguration::class)->execute($domain, [
        'php_version' => '8.5', 'memory_limit' => 512, 'display_errors' => false,
        'custom_settings' => ['opcache.enable' => '1'],
    ]);

    expect($configuration->toIniDirectives())->toMatchArray(['memory_limit' => '512M', 'display_errors' => 'Off', 'opcache.enable' => '1'])
        ->and($configuration->toIniString())->toContain('memory_limit = 512M')
        ->and(\Liberu\ControlPanel\WebHosting\Models\PhpConfiguration::getSupportedVersions())->toContain('8.5');
});
