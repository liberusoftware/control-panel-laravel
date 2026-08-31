<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Actions\AcquireOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\CancelOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\ChangeNodeStatus;
use Liberu\ControlPanel\ControlCore\Actions\CreateOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\DecommissionNode;
use Liberu\ControlPanel\ControlCore\Actions\ExpireNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\RecordInventory;
use Liberu\ControlPanel\ControlCore\Actions\RecordOperationTaskCompensation;
use Liberu\ControlPanel\ControlCore\Actions\RecordOperationTaskStep;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\ReleaseOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\RequestSshOperation;
use Liberu\ControlPanel\ControlCore\Actions\RetryOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\SyncNodeCapabilities;
use Liberu\ControlPanel\ControlCore\Actions\TimeoutOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\TransitionOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\UpdateDesiredState;
use Liberu\ControlPanel\ControlCore\Actions\WriteAuditEntry;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Enums\CompensationStatus;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus;
use Liberu\ControlPanel\ControlCore\Enums\NodeStatus;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Events\NodeRegistered;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;
use Liberu\ControlPanel\ControlCoreApi\ControlCoreApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

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

it('decommissions a node as a terminal transition', function (): void {
    $node = app(RegisterNode::class)->execute(['name' => 'Retiring node', 'hostname' => 'retiring.example.test']);

    $decommissioned = app(DecommissionNode::class)->execute($node);

    expect($decommissioned->status)->toBe(NodeStatus::Decommissioned)
        ->and(fn () => app(DecommissionNode::class)->execute($decommissioned))
        ->toThrow(ValidationException::class);
});

it('decommissions a node through the tenant-scoped API', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $node = app(RegisterNode::class)->execute([
        'team_id' => $team->getKey(), 'name' => 'API retiring node', 'hostname' => 'api-retiring.test',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey().'/decommission')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'decommissioned');
});

it('requires a current team before reading a node through the API', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $user = User::factory()->create();
    $node = app(RegisterNode::class)->execute(['name' => 'Unscoped node', 'hostname' => 'unscoped.test']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey())
        ->assertForbidden();
});

it('returns node etags and rejects stale conditional mutations', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $node = app(RegisterNode::class)->execute([
        'team_id' => $team->getKey(), 'name' => 'Concurrent node', 'hostname' => 'concurrent.test',
    ]);

    $read = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey())
        ->assertOk()
        ->assertHeader('ETag');
    $etag = $read->headers->get('ETag');

    $this->actingAs($user, 'sanctum')
        ->withHeader('If-Match', $etag)
        ->patchJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey().'/desired-state', ['desired_state' => ['status' => 'active']])
        ->assertOk()
        ->assertHeader('ETag');

    $this->actingAs($user, 'sanctum')
        ->withHeader('If-Match', $etag)
        ->patchJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey().'/desired-state', ['desired_state' => ['status' => 'draining']])
        ->assertStatus(412)
        ->assertJsonPath('status', 412);
});

it('expires a past-dated credential and rejects invalid repeats', function (): void {
    $node = app(RegisterNode::class)->execute(['team_id' => 'team-1', 'name' => 'Credential node', 'hostname' => 'credential.test']);
    $credential = app(RegisterNodeCredential::class)->execute([
        'team_id' => 'team-1', 'node_id' => $node->getKey(), 'name' => 'Old key', 'secret' => 'long-enough-secret',
        'expires_at' => now()->subMinute(),
    ]);

    $expired = app(ExpireNodeCredential::class)->execute($credential);

    expect($expired->status)->toBe(CredentialStatus::Expired)
        ->and(fn () => app(ExpireNodeCredential::class)->execute($expired))
        ->toThrow(ValidationException::class);
    expect(fn () => app(ExpireNodeCredential::class)->execute(NodeCredential::query()->create([
        'team_id' => 'team-1', 'node_id' => $node->getKey(), 'name' => 'Future key', 'type' => 'ssh',
        'secret' => 'long-enough-secret', 'status' => CredentialStatus::Active, 'expires_at' => now()->addHour(),
    ])))->toThrow(ValidationException::class);
});

it('expires a credential through the tenant-scoped API', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $node = app(RegisterNode::class)->execute(['team_id' => $team->getKey(), 'name' => 'API credential node', 'hostname' => 'api-credential.test']);
    $credential = app(RegisterNodeCredential::class)->execute([
        'team_id' => $team->getKey(), 'node_id' => $node->getKey(), 'name' => 'Old API key', 'secret' => 'long-enough-secret',
        'expires_at' => now()->subMinute(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/credentials/'.$credential->getKey().'/expire')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'expired');
});

it('updates only a current-team credential through the API', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $node = app(RegisterNode::class)->execute(['team_id' => $team->getKey(), 'name' => 'API node', 'hostname' => 'api.test']);
    $credential = app(RegisterNodeCredential::class)->execute(['team_id' => $team->getKey(), 'node_id' => $node->getKey(), 'name' => 'API key', 'secret' => 'long-enough-secret']);
    $otherNode = app(RegisterNode::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Other node', 'hostname' => 'other.test']);
    $otherCredential = app(RegisterNodeCredential::class)->execute(['team_id' => $otherTeam->getKey(), 'node_id' => $otherNode->getKey(), 'name' => 'Other key', 'secret' => 'long-enough-secret']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/control-core/credentials/'.$otherCredential->getKey(), ['name' => 'Nope'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/control-core/credentials/'.$credential->getKey(), ['name' => 'Release key', 'username' => 'deploy'])->assertOk()->assertJsonPath('data.attributes.name', 'Release key')->assertJsonMissingPath('data.attributes.secret');
});

it('generates an SSH key pair through the tenant-scoped API', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/credentials/generate-key-pair', [
            'bits' => 2048,
            'passphrase' => 'a-secure-passphrase',
            'comment' => 'deploy',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'control-panel-ssh-key-pair')
        ->assertJsonPath('data.attributes.public_key', fn (string $key): bool => str_starts_with($key, 'ssh-rsa '))
        ->assertJsonPath('data.attributes.private_key', fn (string $key): bool => str_contains($key, 'BEGIN ENCRYPTED PRIVATE KEY'));
});

it('queues idempotent SSH deployment and connection-test operations', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $node = app(RegisterNode::class)->execute(['team_id' => $team->getKey(), 'name' => 'SSH node', 'hostname' => 'ssh.test']);

    $deployment = $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey().'/ssh/deploy-key', [
        'username' => 'deploy', 'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIexample', 'idempotency_key' => 'ssh-deploy-1',
    ]);
    $deployment->assertAccepted()->assertJsonPath('data.attributes.operation', 'ssh.deploy-public-key');

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey().'/ssh/deploy-key', [
        'username' => 'deploy', 'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIexample', 'idempotency_key' => 'ssh-deploy-1',
    ])->assertAccepted()->assertJsonPath('data.id', $deployment->json('data.id'));

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey().'/ssh/test-connection', ['idempotency_key' => 'ssh-test-1'])
        ->assertAccepted()->assertJsonPath('data.attributes.operation', 'ssh.test-connection');

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey().'/ssh/test-connection', ['idempotency_key' => 'ssh-deploy-1'])
        ->assertConflict()
        ->assertJsonPath('status', 409);

    expect(OperationTask::query()->where('team_id', $team->getKey())->count())->toBe(2);
    expect(fn () => app(RequestSshOperation::class)->execute((string) $team->getKey(), (string) $node->getKey(), 'ssh.deploy-public-key', 'bad-key', ['username' => 'deploy', 'public_key' => 'not-a-key']))
        ->toThrow(ValidationException::class);
});

it('accepts the standard idempotency header for SSH operations', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $node = app(RegisterNode::class)->execute(['team_id' => $team->getKey(), 'name' => 'Header node', 'hostname' => 'header-ssh.test']);

    $this->actingAs($user, 'sanctum')
        ->withHeader('Idempotency-Key', 'ssh-header-1')
        ->postJson('/api/v1/control-panel/control-core/nodes/'.$node->getKey().'/ssh/test-connection')
        ->assertAccepted()
        ->assertJsonPath('data.attributes.idempotency_key', 'ssh-header-1');
});

it('requeues failed tasks without changing their idempotency identity', function (): void {
    $task = app(CreateOperationTask::class)->execute([
        'team_id' => 'team-1',
        'operation' => 'reconcile',
        'idempotency_key' => 'reconcile-retry-1',
        'payload' => ['scope' => 'node'],
    ]);
    app(TransitionOperationTask::class)->execute($task, TaskStatus::Running);
    $failed = app(TransitionOperationTask::class)->execute($task->refresh(), TaskStatus::Failed, null, 'agent timeout');
    $retried = app(RetryOperationTask::class)->execute($failed);

    expect($retried->getKey())->toBe($task->getKey())
        ->and($retried->status)->toBe(TaskStatus::Pending)
        ->and($retried->idempotency_key)->toBe('reconcile-retry-1')
        ->and($retried->error)->toBeNull()
        ->and($retried->finished_at)->toBeNull();

    expect(fn () => app(RetryOperationTask::class)->execute($retried))
        ->toThrow(ValidationException::class);
});

it('replays timeout-based task requests without changing their idempotency identity', function (): void {
    $attributes = [
        'team_id' => 'team-1',
        'operation' => 'reconcile',
        'idempotency_key' => 'timeout-replay-1',
        'timeout_seconds' => 60,
    ];

    $first = app(CreateOperationTask::class)->execute($attributes);
    $second = app(CreateOperationTask::class)->execute($attributes);

    expect($second->getKey())->toBe($first->getKey())
        ->and($second->timeout_at)->not->toBeNull();
});

it('cancels pending tasks and rejects cancellation after a terminal transition', function (): void {
    $task = app(CreateOperationTask::class)->execute([
        'team_id' => 'team-1',
        'operation' => 'reconcile',
        'idempotency_key' => 'reconcile-cancel-1',
    ]);

    $cancelled = app(CancelOperationTask::class)->execute($task);

    expect($cancelled->status)->toBe(TaskStatus::Cancelled)
        ->and($cancelled->error)->toBe('Cancelled by operator.')
        ->and($cancelled->finished_at)->not->toBeNull();

    expect(fn () => app(CancelOperationTask::class)->execute($cancelled))
        ->toThrow(ValidationException::class);
});

it('marks an expired running task as failed and rejects premature timeouts', function (): void {
    $task = app(CreateOperationTask::class)->execute([
        'team_id' => 'team-1',
        'operation' => 'slow-operation',
        'idempotency_key' => 'timeout-task-1',
        'timeout_at' => now()->addMinute(),
    ]);
    app(TransitionOperationTask::class)->execute($task, TaskStatus::Running);

    expect(fn () => app(TimeoutOperationTask::class)->execute($task->refresh()))
        ->toThrow(ValidationException::class);

    $task->forceFill(['timeout_at' => now()->subSecond()])->save();
    $timedOut = app(TimeoutOperationTask::class)->execute($task->refresh());

    expect($timedOut->status)->toBe(TaskStatus::Failed)
        ->and($timedOut->error)->toBe('Task timed out.');
});

it('records compensation outcomes only after an operation reaches a terminal state', function (): void {
    $task = app(CreateOperationTask::class)->execute([
        'team_id' => 'team-1',
        'operation' => 'provision',
        'idempotency_key' => 'compensation-task-1',
    ]);

    expect(fn () => app(RecordOperationTaskCompensation::class)->execute($task, CompensationStatus::Running))
        ->toThrow(ValidationException::class);

    app(TransitionOperationTask::class)->execute($task, TaskStatus::Running);
    $failed = app(TransitionOperationTask::class)->execute($task->refresh(), TaskStatus::Failed, null, 'remote failure');
    $running = app(RecordOperationTaskCompensation::class)->execute($failed, CompensationStatus::Running);
    $succeeded = app(RecordOperationTaskCompensation::class)->execute($running, CompensationStatus::Succeeded, ['rolled_back' => true]);

    expect($succeeded->compensation_status)->toBe(CompensationStatus::Succeeded)
        ->and($succeeded->compensation_result)->toMatchArray(['rolled_back' => true])
        ->and($succeeded->compensation_finished_at)->not->toBeNull();
});

it('records compensation through the tenant-scoped API', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $task = app(CreateOperationTask::class)->execute(['team_id' => $team->getKey(), 'operation' => 'provision', 'idempotency_key' => 'api-compensation-1']);
    app(TransitionOperationTask::class)->execute($task, TaskStatus::Running);
    app(TransitionOperationTask::class)->execute($task->refresh(), TaskStatus::Failed, null, 'remote failure');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/tasks/'.$task->getKey().'/compensation', ['status' => 'succeeded', 'result' => ['rolled_back' => true]])
        ->assertOk()
        ->assertJsonPath('data.attributes.compensation_status', 'succeeded');
});

it('cancels only a current-team task through the API', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $task = app(CreateOperationTask::class)->execute(['team_id' => $team->getKey(), 'operation' => 'reconcile', 'idempotency_key' => 'api-cancel-1']);
    $otherTask = app(CreateOperationTask::class)->execute(['team_id' => $otherTeam->getKey(), 'operation' => 'reconcile', 'idempotency_key' => 'api-cancel-2']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/tasks/'.$task->getKey().'/cancel')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'cancelled');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/tasks/'.$otherTask->getKey().'/cancel')
        ->assertNotFound();
});

it('records ordered resumable task steps and keeps them tenant-scoped', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $task = app(CreateOperationTask::class)->execute(['team_id' => $team->getKey(), 'operation' => 'provision', 'idempotency_key' => 'step-task-1']);
    $otherTask = app(CreateOperationTask::class)->execute(['team_id' => $otherTeam->getKey(), 'operation' => 'provision', 'idempotency_key' => 'step-task-2']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/tasks/'.$task->getKey().'/steps', [
            'step_key' => 'connect', 'name' => 'Connect to node', 'status' => 'running', 'input' => ['transport' => 'ssh'],
        ])->assertCreated()->assertJsonPath('data.attributes.status', 'running');
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/tasks/'.$task->getKey().'/steps', [
            'step_key' => 'connect', 'name' => 'Connect to node', 'status' => 'succeeded', 'result' => ['latency_ms' => 12],
        ])->assertOk()->assertJsonPath('data.attributes.status', 'succeeded');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/control-core/tasks/'.$task->getKey().'/steps')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.result.latency_ms', 12);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/control-core/tasks/'.$otherTask->getKey().'/steps')
        ->assertNotFound();
    expect($task->steps()->count())->toBe(1);
    expect(fn () => app(RecordOperationTaskStep::class)->execute($task, ['step_key' => '', 'name' => '']))
        ->toThrow(ValidationException::class);
});

it('retries a failed task through the tenant-scoped API', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $task = app(CreateOperationTask::class)->execute(['team_id' => $team->getKey(), 'operation' => 'reconcile', 'idempotency_key' => 'api-retry-1']);
    app(TransitionOperationTask::class)->execute($task, TaskStatus::Running);
    app(TransitionOperationTask::class)->execute($task->refresh(), TaskStatus::Failed, null, 'agent timeout');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/control-core/tasks/'.$task->getKey().'/retry')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'pending')
        ->assertJsonMissingPath('data.attributes.idempotency_key');

    expect($task->refresh()->status)->toBe(TaskStatus::Pending);
});

it('accepts the standard idempotency header for queued task requests', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    $this->actingAs($user, 'sanctum')
        ->withHeader('Idempotency-Key', 'header-task-1')
        ->postJson('/api/v1/control-panel/control-core/tasks', ['operation' => 'reconcile', 'payload' => ['scope' => 'node']])
        ->assertCreated()
        ->assertJsonPath('data.attributes.idempotency_key', 'header-task-1');
});

it('returns conflict when a task idempotency key is reused for another request', function (): void {
    app()->register(ControlCoreApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    $this->actingAs($user, 'sanctum')->withHeader('Idempotency-Key', 'conflict-task-1')
        ->postJson('/api/v1/control-panel/control-core/tasks', ['operation' => 'reconcile', 'payload' => ['scope' => 'node']])
        ->assertCreated();
    $this->actingAs($user, 'sanctum')->withHeader('Idempotency-Key', 'conflict-task-1')
        ->postJson('/api/v1/control-panel/control-core/tasks', ['operation' => 'provision', 'payload' => ['scope' => 'node']])
        ->assertConflict()
        ->assertJsonPath('status', 409);
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
