<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\RevokeNodeCredential;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource\Pages\CreateNodeCredential;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(ControlCoreServiceProvider::class);
    $this->artisan('migrate');
});

it('rejects credentials for a node outside the current team', function (): void {
    $node = app(RegisterNode::class)->execute(['team_id' => 'team-1', 'name' => 'Private node', 'hostname' => 'private.test']);

    expect(fn () => app(RegisterNodeCredential::class)->execute([
        'team_id' => 'team-2', 'node_id' => $node->getKey(), 'name' => 'Invalid key', 'secret' => 'a-secret-value',
    ]))->toThrow(ValidationException::class);
});

it('registers encrypted managed credentials and supports revocation', function (): void {
    $node = app(RegisterNode::class)->execute(['team_id' => 'team-1', 'name' => 'Managed node', 'hostname' => 'node.test']);
    $credential = app(RegisterNodeCredential::class)->execute([
        'team_id' => 'team-1', 'node_id' => $node->getKey(), 'name' => 'Deploy key', 'type' => 'ssh',
        'username' => 'root', 'secret' => 'a-secret-value', 'metadata' => ['scope' => 'deploy'],
    ]);

    expect($credential->secret)->toBe('a-secret-value')
        ->and($credential->toArray())->not->toHaveKey('secret')
        ->and(DB::table('control_panel_node_credentials')->whereKey($credential->getKey())->value('secret'))->not->toBe('a-secret-value');

    $revoked = app(RevokeNodeCredential::class)->execute($credential);
    expect($revoked->status)->toBe(CredentialStatus::Revoked);
});

it('exposes a tenant-scoped Filament create workflow for node credentials', function (): void {
    expect(NodeCredentialResource::getPages()['create']->getPage())->toBe(CreateNodeCredential::class);
});
