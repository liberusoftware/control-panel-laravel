<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Actions\ActivateDatabase;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseUser;
use Liberu\ControlPanel\Databases\Actions\GrantDatabasePrivilege;
use Liberu\ControlPanel\Databases\DatabasesServiceProvider;
use Liberu\ControlPanel\Databases\Enums\DatabaseStatus;
use Liberu\ControlPanel\Databases\Events\DatabaseCreated;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(DatabasesServiceProvider::class);
    $this->artisan('migrate');
    DatabaseEngine::query()->create(['name' => 'Primary', 'driver' => 'mysql', 'host' => 'db.internal']);
});

it('creates a provisioning database and emits an after-commit event', function (): void {
    Event::fake();
    $engine = DatabaseEngine::query()->firstOrFail();
    $database = app(CreateDatabase::class)->execute(['team_id' => 'team-1', 'engine_id' => $engine->getKey(), 'name' => 'customer_app']);

    expect($database->status)->toBe(DatabaseStatus::Provisioning)->and(Schema::hasTable('control_panel_databases'))->toBeTrue();
    Event::assertDispatched(DatabaseCreated::class);
});

it('activates a database', function (): void {
    $engine = DatabaseEngine::query()->firstOrFail();
    $database = app(CreateDatabase::class)->execute(['engine_id' => $engine->getKey(), 'name' => 'customer_app']);

    expect(app(ActivateDatabase::class)->execute($database)->status)->toBe(DatabaseStatus::Active);
});

it('rejects archived database activation and missing identity', function (): void {
    $engine = DatabaseEngine::query()->firstOrFail();
    $database = app(CreateDatabase::class)->execute(['engine_id' => $engine->getKey(), 'name' => 'customer_app']);
    $database->update(['status' => DatabaseStatus::Archived]);

    expect(fn () => app(ActivateDatabase::class)->execute($database))->toThrow(ValidationException::class)
        ->and(fn () => app(CreateDatabase::class)->execute(['name' => 'missing-engine']))->toThrow(ValidationException::class);
});

it('stores encrypted database credentials and allow-listed privileges', function (): void {
    $engine = DatabaseEngine::query()->firstOrFail();
    $database = app(CreateDatabase::class)->execute(['team_id' => 'team-1', 'engine_id' => $engine->getKey(), 'name' => 'customer_app']);
    $user = app(CreateDatabaseUser::class)->execute($database, ['username' => 'app_user', 'password' => 'a-very-long-secret-password']);
    $privilege = app(GrantDatabasePrivilege::class)->execute($user, 'SELECT', 'customer_data.*');

    expect($user->password)->toBe('a-very-long-secret-password')
        ->and($user->toArray())->not->toHaveKey('password')
        ->and($privilege->privilege)->toBe('select');
    expect(fn () => app(GrantDatabasePrivilege::class)->execute($user, 'drop', '*'))
        ->toThrow(ValidationException::class);
});
