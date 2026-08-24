<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\RevokeNodeCredential;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(ControlCoreServiceProvider::class);
    $this->artisan('migrate');
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
