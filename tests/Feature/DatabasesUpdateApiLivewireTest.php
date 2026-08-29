<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\Actions\UpdateDatabase;
use Liberu\ControlPanel\Databases\DatabasesServiceProvider;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;
use Liberu\ControlPanel\DatabasesApi\DatabasesApiServiceProvider;
use Liberu\ControlPanel\DatabasesLivewire\Components\DatabaseInventory;
use Liberu\ControlPanel\DatabasesLivewire\DatabasesLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(DatabasesServiceProvider::class);
    app()->register(DatabasesApiServiceProvider::class);
    app()->register(DatabasesLivewireServiceProvider::class);
    $this->artisan('migrate');
    $this->engine = DatabaseEngine::query()->create(['name' => 'Primary', 'driver' => 'mysql', 'host' => 'db.internal']);
});

it('updates only a current-team database through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateDatabase::class)->execute(['team_id' => $otherTeam->getKey(), 'engine_id' => $this->engine->getKey(), 'name' => 'foreign_db']);
    $owned = app(CreateDatabase::class)->execute(['team_id' => $team->getKey(), 'engine_id' => $this->engine->getKey(), 'name' => 'owned_db']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/databases/'.$foreign->getKey(), ['name' => 'nope'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/databases/'.$owned->getKey(), ['name' => 'updated_db'])->assertOk()->assertJsonPath('data.attributes.name', 'updated_db');
});

it('updates only a current-team database from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateDatabase::class)->execute(['team_id' => $otherTeam->getKey(), 'engine_id' => $this->engine->getKey(), 'name' => 'foreign_db']);
    $owned = app(CreateDatabase::class)->execute(['team_id' => $team->getKey(), 'engine_id' => $this->engine->getKey(), 'name' => 'owned_db']);
    $inventory = app(DatabaseInventory::class);
    $this->actingAs($user);

    expect(fn () => $inventory->update($foreign->getKey(), ['name' => 'nope', 'engine_id' => $this->engine->getKey(), 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'], app(UpdateDatabase::class)))->toThrow(ModelNotFoundException::class);
    $inventory->update($owned->getKey(), ['name' => 'updated_db', 'engine_id' => $this->engine->getKey(), 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'], app(UpdateDatabase::class));

    expect(Database::query()->findOrFail($owned->getKey())->name)->toBe('updated_db');
});
