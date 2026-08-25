<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\OsAdapters\Actions\RecordOsResource;
use Liberu\ControlPanel\OsAdapters\Actions\RecordSupportMatrix;
use Liberu\ControlPanel\OsAdapters\Models\FilesystemMount;
use Liberu\ControlPanel\OsAdapters\Models\FirewallRule;
use Liberu\ControlPanel\OsAdapters\Models\OsPackage;
use Liberu\ControlPanel\OsAdapters\Models\OsService;
use Liberu\ControlPanel\OsAdapters\Models\OsUser;
use Liberu\ControlPanel\OsAdapters\Models\PackageRepository;
use Liberu\ControlPanel\OsAdapters\OsAdaptersServiceProvider;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(OsAdaptersServiceProvider::class);
    $this->artisan('migrate');
});

it('records package, service, firewall, user, filesystem, and repository inventory', function (): void {
    $record = app(RecordOsResource::class);
    $common = ['team_id' => 'team-1', 'node_id' => 'node-1'];

    $package = $record->execute(OsPackage::class, $common + ['name' => 'nginx', 'version' => '1.26', 'status' => 'installed']);
    $service = $record->execute(OsService::class, $common + ['name' => 'nginx', 'status' => 'running', 'enabled' => true]);
    $firewall = $record->execute(FirewallRule::class, $common + ['direction' => 'inbound', 'action' => 'allow', 'port' => 443]);
    $user = $record->execute(OsUser::class, $common + ['username' => 'deploy', 'status' => 'active']);
    $filesystem = $record->execute(FilesystemMount::class, $common + ['device' => '/dev/vda1', 'mount_path' => '/']);
    $repository = $record->execute(PackageRepository::class, $common + ['name' => 'base', 'url' => 'https://packages.example.test/base']);

    expect($package->name)->toBe('nginx')->and($service->enabled)->toBeTrue()->and($firewall->port)->toBe(443)->and($user->username)->toBe('deploy')->and($filesystem->mount_path)->toBe('/')->and($repository->trusted)->toBeFalse();
});

it('requires a tenant and node for adapter resources', function (): void {
    expect(fn () => app(RecordOsResource::class)->execute(OsPackage::class, ['name' => 'nginx']))
        ->toThrow(ValidationException::class);
});

it('records support matrix decisions independently from node inventory', function (): void {
    $entry = app(RecordSupportMatrix::class)->execute(['operating_system' => 'AlmaLinux', 'version' => '9', 'capability' => 'firewall', 'supported' => true]);

    expect($entry->supported)->toBeTrue()->and($entry->capability)->toBe('firewall');
});
