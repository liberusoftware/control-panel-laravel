<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\ApiAutomationServiceProvider;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;
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

it('revokes only a current-team API credential through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $credential = app(RegisterApiCredential::class)->execute(['team_id' => $team->getKey(), 'name' => 'Deploy']);
    $otherCredential = app(RegisterApiCredential::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Other']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/api-and-automation/credentials/'.$otherCredential->getKey().'/revoke')
        ->assertNotFound();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/api-and-automation/credentials/'.$credential->getKey().'/revoke')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'revoked')
        ->assertJsonMissingPath('data.attributes.secret');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/api-and-automation/credentials/'.$credential->getKey().'/revoke')
        ->assertUnprocessable();
});

it('updates only a current-team API credential through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $credential = app(RegisterApiCredential::class)->execute(['team_id' => $team->getKey(), 'name' => 'Deploy']);
    $otherCredential = app(RegisterApiCredential::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Other']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/api-and-automation/credentials/'.$otherCredential->getKey(), ['name' => 'Nope'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/api-and-automation/credentials/'.$credential->getKey(), ['name' => 'Release', 'scopes' => ['release']])->assertOk()->assertJsonPath('data.attributes.name', 'Release')->assertJsonMissingPath('data.attributes.secret');
});

it('updates only a current-team webhook through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $webhook = app(RegisterWebhook::class)->execute(['team_id' => $team->getKey(), 'name' => 'Events', 'url' => 'https://example.test/hooks']);
    $otherWebhook = app(RegisterWebhook::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Other', 'url' => 'https://other.test/hooks']);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/control-panel/api-and-automation/webhooks/'.$otherWebhook->getKey(), ['name' => 'Nope'])
        ->assertNotFound();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/control-panel/api-and-automation/webhooks/'.$webhook->getKey(), ['name' => 'Updated', 'retry_limit' => 8])
        ->assertOk()
        ->assertJsonPath('data.attributes.name', 'Updated')
        ->assertJsonPath('data.attributes.retry_limit', 8);
});

it('bounds automation pagination', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/api-and-automation?per_page=1000')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('rejects orchestration idempotency-key reuse for a different request', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $template = AutomationTemplate::query()->create([
        'team_id' => $team->getKey(),
        'name' => 'Provision',
        'version' => '1',
        'inputs' => ['hostname'],
        'steps' => [['action' => 'provision']],
        'active' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->withHeader('Idempotency-Key', 'orchestration-key-1')
        ->postJson('/api/v1/control-panel/api-and-automation/templates/'.$template->getKey().'/runs', ['input' => ['hostname' => 'one.test']])
        ->assertAccepted();

    $this->actingAs($user, 'sanctum')
        ->withHeader('Idempotency-Key', 'orchestration-key-1')
        ->postJson('/api/v1/control-panel/api-and-automation/templates/'.$template->getKey().'/runs', ['input' => ['hostname' => 'two.test']])
        ->assertConflict()
        ->assertJsonPath('status', 409);
});
