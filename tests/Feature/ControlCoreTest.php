<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Actions\ChangeNodeStatus;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Enums\NodeStatus;
use Liberu\ControlPanel\ControlCore\Events\NodeRegistered;
use Liberu\ControlPanel\ControlCore\Models\Node;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(ControlCoreServiceProvider::class);
    $this->artisan('migrate');
});

it('registers a node and keeps credentials encrypted and hidden', function (): void {
    Event::fake();

    $node = app(RegisterNode::class)->execute([
        'team_id' => 'team-1',
        'name' => 'Primary node',
        'hostname' => 'node.example.test',
        'platform' => 'almalinux',
        'credentials' => ['username' => 'root', 'private_key' => 'secret-key'],
    ]);

    expect($node->status)->toBe(NodeStatus::Pending)
        ->and($node->credentials)->toMatchArray(['username' => 'root', 'private_key' => 'secret-key'])
        ->and($node->toArray())->not->toHaveKey('credentials');

    expect(DB::table('control_panel_nodes')->where('id', $node->getKey())->value('credentials'))
        ->not->toContain('secret-key');

    Event::assertDispatched(NodeRegistered::class);
});

it('does not allow a decommissioned node to be reactivated', function (): void {
    $node = app(RegisterNode::class)->execute([
        'name' => 'Retired node',
        'hostname' => 'retired.example.test',
    ]);

    app(ChangeNodeStatus::class)->execute($node, NodeStatus::Decommissioned);

    expect(fn () => app(ChangeNodeStatus::class)->execute($node, NodeStatus::Active))
        ->toThrow(ValidationException::class);
});

it('never exposes credentials through a node query', function (): void {
    $node = Node::query()->create([
        'team_id' => 'team-1',
        'name' => 'Query node',
        'hostname' => 'query.example.test',
        'status' => NodeStatus::Active,
        'credentials' => ['token' => 'do-not-expose'],
    ]);

    expect($node->toJson())->not->toContain('do-not-expose');
});
