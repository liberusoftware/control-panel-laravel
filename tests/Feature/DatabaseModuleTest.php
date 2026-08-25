<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Actions\ConfigureRemoteAccess;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseBackup;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseUser;
use Liberu\ControlPanel\Databases\Actions\GrantDatabasePrivilege;
use Liberu\ControlPanel\Databases\Actions\RecordDatabaseHealth;
use Liberu\ControlPanel\Databases\Actions\RequestDatabaseUpgrade;
use Liberu\ControlPanel\Databases\DatabasesServiceProvider;
use Liberu\ControlPanel\Databases\Enums\BackupStatus;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(DatabasesServiceProvider::class);
    $this->artisan('migrate');
});

it('keeps database access tenant-scoped and credentials encrypted', function (): void {
    $engine = DatabaseEngine::query()->create(['team_id' => 'team-1', 'name' => 'Primary', 'driver' => 'mysql', 'version' => '8.4', 'host' => 'db.test']);
    $database = app(CreateDatabase::class)->execute(['team_id' => 'team-1', 'engine_id' => $engine->getKey(), 'name' => 'app']);
    $user = app(CreateDatabaseUser::class)->execute($database, ['username' => 'app', 'password' => 'a-very-long-test-password']);
    $privilege = app(GrantDatabasePrivilege::class)->execute($user, 'select', 'app.*');

    expect($user->toArray())->not->toHaveKey('password')
        ->and($user->password)->toBe('a-very-long-test-password')
        ->and($privilege->team_id)->toBe('team-1');
});

it('records backup, health, upgrade, and restricted remote access workflows', function (): void {
    $engine = DatabaseEngine::query()->create(['team_id' => 'team-1', 'name' => 'Primary', 'driver' => 'mysql', 'version' => '8.4', 'host' => 'db.test']);
    $database = app(CreateDatabase::class)->execute(['team_id' => 'team-1', 'engine_id' => $engine->getKey(), 'name' => 'app']);
    $backup = app(CreateDatabaseBackup::class)->execute($database, ['destination' => 's3://bucket', 'type' => 'database']);
    $health = app(RecordDatabaseHealth::class)->execute($database, true, 12, 'OK');
    $upgrade = app(RequestDatabaseUpgrade::class)->execute($database, '8.5');
    $access = app(ConfigureRemoteAccess::class)->execute($database, ['source_cidr' => '10.0.0.0/24', 'port' => 3306]);

    expect($backup->status)->toBe(BackupStatus::Pending)
        ->and($health->healthy)->toBeTrue()
        ->and($upgrade->to_version)->toBe('8.5')
        ->and($access->tls_required)->toBeTrue();

    expect(fn () => app(ConfigureRemoteAccess::class)->execute($database, ['source_cidr' => 'not-an-ip', 'port' => 3306]))
        ->toThrow(ValidationException::class);
});
