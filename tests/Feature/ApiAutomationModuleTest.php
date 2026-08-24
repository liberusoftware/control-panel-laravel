<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\StartOrchestration;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;
use Liberu\ControlPanel\ApiAutomation\ApiAutomationServiceProvider;

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

it('rejects insecure webhook endpoints and makes orchestration idempotent', function (): void {
    expect(fn () => app(RegisterWebhook::class)->execute(['team_id' => 'team-1', 'name' => 'Insecure', 'url' => 'http://example.test/hooks']))
        ->toThrow(ValidationException::class);

    $template = AutomationTemplate::query()->create(['team_id' => 'team-1', 'name' => 'Provision', 'version' => '1', 'inputs' => ['hostname'], 'steps' => [['action' => 'provision']], 'active' => true]);
    $first = app(StartOrchestration::class)->execute($template, ['hostname' => 'node.test'], 'team-1', 'run-1');
    $second = app(StartOrchestration::class)->execute($template, ['hostname' => 'node.test'], 'team-1', 'run-1');

    expect($second->getKey())->toBe($first->getKey());
});
