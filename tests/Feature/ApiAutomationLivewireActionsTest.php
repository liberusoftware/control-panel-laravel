<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\ApiAutomation\Actions\PauseWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\ResumeWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\RevokeApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\UpdateWebhook;
use Liberu\ControlPanel\ApiAutomation\ApiAutomationServiceProvider;
use Liberu\ControlPanel\ApiAutomationLivewire\ApiAutomationLivewireServiceProvider;
use Liberu\ControlPanel\ApiAutomationLivewire\Components\CredentialInventory;
use Liberu\ControlPanel\ApiAutomationLivewire\Components\WebhookInventory;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(ApiAutomationServiceProvider::class);
    app()->register(ApiAutomationLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('pauses and resumes only a current-team webhook from Livewire', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $webhook = app(RegisterWebhook::class)->execute([
        'team_id' => $team->getKey(), 'name' => 'Events', 'url' => 'https://example.test/hooks',
    ]);

    $this->actingAs($user);
    $inventory = app(WebhookInventory::class);
    $inventory->pause($webhook->getKey(), app(PauseWebhook::class));
    expect($webhook->refresh()->status)->toBe('paused');

    $inventory->resume($webhook->getKey(), app(ResumeWebhook::class));
    expect($webhook->refresh()->status)->toBe('active');
});

it('revokes only a current-team API credential from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $credential = app(RegisterApiCredential::class)->execute(['team_id' => $team->getKey(), 'name' => 'Deploy']);
    $otherCredential = app(RegisterApiCredential::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Other']);

    $this->actingAs($user);
    $inventory = app(CredentialInventory::class);
    $inventory->revoke($credential->getKey(), app(RevokeApiCredential::class));
    expect($credential->refresh()->status)->toBe('revoked');

    expect(fn () => $inventory->revoke($otherCredential->getKey(), app(RevokeApiCredential::class)))
        ->toThrow(ModelNotFoundException::class);
});

it('updates only a current-team webhook from Livewire', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $webhook = app(RegisterWebhook::class)->execute(['team_id' => $team->getKey(), 'name' => 'Events', 'url' => 'https://example.test/hooks']);

    $this->actingAs($user);
    $inventory = app(WebhookInventory::class);
    $inventory->update($webhook->getKey(), ['name' => 'Updated', 'url' => 'https://hooks.test/events', 'retry_limit' => 7], app(UpdateWebhook::class));

    expect($webhook->refresh()->name)->toBe('Updated')->and($webhook->retry_limit)->toBe(7);
});

it('rejects a foreign-team webhook update from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $webhook = app(RegisterWebhook::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Other', 'url' => 'https://other.test/hooks']);

    $this->actingAs($user);
    $inventory = app(WebhookInventory::class);

    expect(fn () => $inventory->update($webhook->getKey(), ['name' => 'Nope', 'url' => 'https://hooks.test/events', 'retry_limit' => 5], app(UpdateWebhook::class)))
        ->toThrow(ModelNotFoundException::class);
});
