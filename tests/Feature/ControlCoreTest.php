<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Actions\AcquireOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\ChangeNodeStatus;
use Liberu\ControlPanel\ControlCore\Actions\CreateOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\RecordInventory;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Actions\SyncNodeCapabilities;
use Liberu\ControlPanel\ControlCore\Actions\TransitionOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\UpdateDesiredState;
use Liberu\ControlPanel\ControlCore\Actions\WriteAuditEntry;
use Liberu\ControlPanel\ControlCore\Actions\ReleaseOperationLock;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Enums\NodeStatus;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Events\NodeRegistered;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

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

it('deduplicates operation tasks by team and idempotency key', function (): void {
    Event::fake();

    $attributes = ['team_id' => 'team-1', 'operation' => 'node.provision', 'idempotency_key' => 'request-1', 'payload' => ['hostname' => 'node.test']];
    $first = app(CreateOperationTask::class)->execute($attributes);
    $second = app(CreateOperationTask::class)->execute($attributes);

    expect($second->getKey())->toBe($first->getKey())->and(OperationTask::query()->count())->toBe(1);
});

it('records inventory and prevents concurrent operation locks', function (): void {
    $node = app(RegisterNode::class)->execute(['team_id' => 'team-1', 'name' => 'Inventory node', 'hostname' => 'inventory.test']);
    $record = app(RecordInventory::class)->execute(['team_id' => 'team-1', 'node_id' => $node->getKey(), 'kind' => 'package', 'record_key' => 'php', 'value' => ['version' => '8.5']]);
    $lock = app(AcquireOperationLock::class)->execute('team-1', $node->getKey(), 'provision', 'test-suite');

    expect($record->value)->toMatchArray(['version' => '8.5'])->and($lock->owner)->toBe('test-suite');
    expect(fn () => app(AcquireOperationLock::class)->execute('team-1', $node->getKey(), 'provision', 'second-owner'))
        ->toThrow(ValidationException::class);
});

it('requires lock ownership to release a lock and scopes locks to the node team', function (): void {
    $node = app(RegisterNode::class)->execute(['team_id' => 'team-1', 'name' => 'Locked node', 'hostname' => 'locked.test']);
    $lock = app(AcquireOperationLock::class)->execute('team-1', $node->getKey(), 'deploy', 'owner-1');

    expect(fn () => app(ReleaseOperationLock::class)->execute($lock, 'owner-2'))
        ->toThrow(ValidationException::class);

    app(ReleaseOperationLock::class)->execute($lock->refresh(), 'owner-1');
    expect(DB::table('control_panel_operation_locks')->where('id', $lock->getKey())->exists())->toBeFalse();
    expect(fn () => app(AcquireOperationLock::class)->execute('team-2', $node->getKey(), 'deploy', 'owner-2'))
        ->toThrow(ValidationException::class);
});

it('updates desired state, synchronizes capabilities, transitions tasks, and records audit evidence', function (): void {
    Event::fake();
    $node = app(RegisterNode::class)->execute(['team_id' => 'team-1', 'name' => 'Managed node', 'hostname' => 'managed.test']);

    app(UpdateDesiredState::class)->execute($node, ['php' => '8.5']);
    app(SyncNodeCapabilities::class)->execute($node, [['name' => 'web-server', 'version' => '1.0']]);
    $task = app(CreateOperationTask::class)->execute(['team_id' => 'team-1', 'node_id' => $node->getKey(), 'operation' => 'reconcile', 'idempotency_key' => 'reconcile-1']);
    app(TransitionOperationTask::class)->execute($task, TaskStatus::Running);
    $task = app(TransitionOperationTask::class)->execute($task->refresh(), TaskStatus::Succeeded, ['changed' => true]);
    $audit = app(WriteAuditEntry::class)->execute('node.reconciled', 'team-1', 'user-1', 'node', $node->getKey(), ['task_id' => $task->getKey()]);

    expect($node->refresh()->desired_state)->toMatchArray(['php' => '8.5'])
        ->and($node->capabilities)->toHaveCount(1)
        ->and($task->status)->toBe(TaskStatus::Succeeded)
        ->and($audit->context)->toMatchArray(['task_id' => $task->getKey()]);
});
