<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Databases\Actions\ActivateDatabase;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\DatabasesServiceProvider;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;
use Liberu\ControlPanel\DatabasesApi\DatabasesApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(DatabasesServiceProvider::class);
    app()->register(DatabasesApiServiceProvider::class);
    $this->artisan('migrate');
});

it('suspends and archives only a current-team database through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $engine = DatabaseEngine::query()->create(['team_id' => $team->getKey(), 'name' => 'Primary', 'driver' => 'mysql', 'host' => 'db.internal']);
    $database = app(CreateDatabase::class)->execute(['team_id' => $team->getKey(), 'engine_id' => $engine->getKey(), 'name' => 'customer_app']);
    $otherEngine = DatabaseEngine::query()->create(['team_id' => $otherTeam->getKey(), 'name' => 'Other', 'driver' => 'mysql', 'host' => 'other.internal']);
    $otherDatabase = app(CreateDatabase::class)->execute(['team_id' => $otherTeam->getKey(), 'engine_id' => $otherEngine->getKey(), 'name' => 'other_app']);
    app(ActivateDatabase::class)->execute($database);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/databases/'.$database->getKey().'/suspend')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'suspended');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/databases/'.$database->getKey().'/archive')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'archived');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/databases/'.$otherDatabase->getKey().'/archive')
        ->assertNotFound();
});
