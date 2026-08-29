<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\ApiAutomationServiceProvider;
use Liberu\ControlPanel\ApiAutomationApi\ApiAutomationApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(ApiAutomationServiceProvider::class);
    app()->register(ApiAutomationApiServiceProvider::class);
    $this->artisan('migrate');
});

it('pauses and resumes only a current-team webhook through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $webhook = app(RegisterWebhook::class)->execute([
        'team_id' => $team->getKey(), 'name' => 'Events', 'url' => 'https://example.test/hooks',
    ]);
    $otherWebhook = app(RegisterWebhook::class)->execute([
        'team_id' => $otherTeam->getKey(), 'name' => 'Other events', 'url' => 'https://other.test/hooks',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/api-and-automation/webhooks/'.$webhook->getKey().'/pause')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'paused');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/api-and-automation/webhooks/'.$webhook->getKey().'/resume')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'active');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/api-and-automation/webhooks/'.$otherWebhook->getKey().'/pause')
        ->assertNotFound();
});

it('rejects webhook state changes without a current team', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => null]);
    $webhook = app(RegisterWebhook::class)->execute([
        'team_id' => $team->getKey(), 'name' => 'Events', 'url' => 'https://example.test/hooks',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/api-and-automation/webhooks/'.$webhook->getKey().'/pause')
        ->assertForbidden();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/api-and-automation/webhooks/'.$webhook->getKey().'/resume')
        ->assertForbidden();
});

it('bounds automation pagination', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/api-and-automation?per_page=1000')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});
