<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Actions\PauseWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\ResumeWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\RevokeApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\StartOrchestration;
use Liberu\ControlPanel\ApiAutomation\Actions\UpdateWebhook;
use Liberu\ControlPanel\ApiAutomation\ApiAutomationServiceProvider;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(ApiAutomationServiceProvider::class);
    $this->artisan('migrate');
});

it('stores scoped credentials and webhook secrets encrypted and hidden', function (): void {
    $credential = app(RegisterApiCredential::class)->execute(['team_id' => 'team-1', 'name' => 'Deploy', 'scopes' => ['deploy'], 'secret' => 'credential-secret']);
    $webhook = app(RegisterWebhook::class)->execute(['team_id' => 'team-1', 'name' => 'Events', 'url' => 'https://example.test/hooks', 'secret' => 'webhook-secret']);

    expect($credential->toArray())->not->toHaveKey('secret')
        ->and($credential->secret)->toBe('credential-secret')
        ->and($webhook->toArray())->not->toHaveKey('secret')
        ->and($webhook->secret)->toBe('webhook-secret');
});

it('revokes an active API credential and rejects repeat revocation', function (): void {
    $credential = app(RegisterApiCredential::class)->execute(['team_id' => 'team-1', 'name' => 'Deploy']);

    expect(app(RevokeApiCredential::class)->execute($credential)->status)->toBe('revoked')
        ->and(fn () => app(RevokeApiCredential::class)->execute($credential->refresh()))
        ->toThrow(ValidationException::class);
});

it('rejects insecure webhook endpoints and makes orchestration idempotent', function (): void {
    expect(fn () => app(RegisterWebhook::class)->execute(['team_id' => 'team-1', 'name' => 'Insecure', 'url' => 'http://example.test/hooks']))
        ->toThrow(ValidationException::class);

    $template = AutomationTemplate::query()->create(['team_id' => 'team-1', 'name' => 'Provision', 'version' => '1', 'inputs' => ['hostname'], 'steps' => [['action' => 'provision']], 'active' => true]);
    $first = app(StartOrchestration::class)->execute($template, ['hostname' => 'node.test'], 'team-1', 'run-1');
    $second = app(StartOrchestration::class)->execute($template, ['hostname' => 'node.test'], 'team-1', 'run-1');

    expect($second->getKey())->toBe($first->getKey());
});

it('updates webhook delivery settings without changing lifecycle state', function (): void {
    $webhook = app(RegisterWebhook::class)->execute(['team_id' => 'team-1', 'name' => 'Events', 'url' => 'https://example.test/hooks']);

    $updated = app(UpdateWebhook::class)->execute($webhook, ['name' => 'Updated events', 'url' => 'https://hooks.test/events', 'retry_limit' => 10]);

    expect($updated->name)->toBe('Updated events')
        ->and($updated->url)->toBe('https://hooks.test/events')
        ->and($updated->retry_limit)->toBe(10)
        ->and($updated->status)->toBe('active');
});

it('pauses and recovers failed webhooks while resetting delivery failures', function (): void {
    $webhook = app(RegisterWebhook::class)->execute([
        'team_id' => 'team-1', 'name' => 'Events', 'url' => 'https://example.test/hooks',
    ]);

    $paused = app(PauseWebhook::class)->execute($webhook);
    expect($paused->status)->toBe('paused');

    $failed = $paused->forceFill(['status' => 'failed', 'failure_count' => 4])->save() ? $paused->refresh() : $paused;
    $resumed = app(ResumeWebhook::class)->execute($failed);

    expect($resumed->status)->toBe('active')->and($resumed->failure_count)->toBe(0)
        ->and(fn () => app(ResumeWebhook::class)->execute($resumed))->toThrow(ValidationException::class);
});
