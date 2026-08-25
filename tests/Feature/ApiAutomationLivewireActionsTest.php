<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\ApiAutomation\Actions\PauseWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\ResumeWebhook;
use Liberu\ControlPanel\ApiAutomation\ApiAutomationServiceProvider;
use Liberu\ControlPanel\ApiAutomationLivewire\ApiAutomationLivewireServiceProvider;
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
