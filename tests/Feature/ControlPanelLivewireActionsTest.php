<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Liberu\ControlPanel\Accounts\AccountsServiceProvider;
use Liberu\ControlPanel\Accounts\Actions\CreateAccount;
use Liberu\ControlPanel\Accounts\Actions\DelegateAccount;
use Liberu\ControlPanel\Accounts\Actions\RevokeDelegation;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\Accounts\Models\AccountDelegation;
use Liberu\ControlPanel\AccountsLivewire\AccountsLivewireServiceProvider;
use Liberu\ControlPanel\AccountsLivewire\Components\AccountFeatureInventory;
use Liberu\ControlPanel\AccountsLivewire\Components\AccountInventory;
use Liberu\ControlPanel\ControlCore\Actions\AcquireOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\CreateOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\ReleaseOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\RevokeNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\TransitionOperationTask;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Models\OperationLock;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;
use Liberu\ControlPanel\ControlCoreLivewire\Components\CredentialInventory;
use Liberu\ControlPanel\ControlCoreLivewire\Components\OperationsInventory;
use Liberu\ControlPanel\ControlCoreLivewire\ControlCoreLivewireServiceProvider;
use Liberu\ControlPanel\Databases\Actions\ActivateDatabase;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\DatabasesServiceProvider;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;
use Liberu\ControlPanel\DatabasesLivewire\Components\DatabaseInventory;
use Liberu\ControlPanel\WebHosting\Actions\CheckApplicationHealth;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHosting\WebHostingServiceProvider;
use Liberu\ControlPanel\WebHostingLivewire\Components\HostingResourceInventory;
use Liberu\ControlPanel\WebHostingLivewire\WebHostingLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(AccountsServiceProvider::class);
    app()->register(AccountsLivewireServiceProvider::class);
    app()->register(ControlCoreServiceProvider::class);
    app()->register(ControlCoreLivewireServiceProvider::class);
    app()->register(WebHostingServiceProvider::class);
    app()->register(WebHostingLivewireServiceProvider::class);
    app()->register(DatabasesServiceProvider::class);
    $this->artisan('migrate');
});

it('releases only a current-team operation lock owned by the supplied owner', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $node = app(RegisterNode::class)->execute(['team_id' => $team->getKey(), 'name' => 'node', 'hostname' => 'node.test']);
    $lock = app(AcquireOperationLock::class)->execute($team->getKey(), $node->getKey(), 'deploy', 'owner-1');

    $this->actingAs($user);
    $component = app(OperationsInventory::class);
    $component->lockOwner = 'owner-1';
    $component->releaseLock($lock->getKey(), app(ReleaseOperationLock::class));

    expect(OperationLock::query()->find($lock->getKey()))->toBeNull();
});

it('checks application health from the tenant-scoped hosting inventory', function (): void {
    Http::fake(['https://livewire.test' => Http::response('ok', 200)]);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $domain = app(CreateDomain::class)->execute(['team_id' => $team->getKey(), 'hostname' => 'livewire.test']);
    $application = HostedApplication::query()->create([
        'team_id' => $team->getKey(), 'domain_id' => $domain->getKey(), 'name' => 'Livewire app',
        'type' => 'laravel', 'document_root' => '/srv/livewire', 'status' => 'installed',
    ]);

    $this->actingAs($user);
    app(HostingResourceInventory::class)->checkApplication($application->getKey(), app(CheckApplicationHealth::class));

    expect($application->performanceMetrics()->count())->toBe(1);
});

it('revokes only a current-team credential from the Livewire inventory', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $node = app(RegisterNode::class)->execute(['team_id' => $team->getKey(), 'name' => 'node', 'hostname' => 'node.test']);
    $credential = app(RegisterNodeCredential::class)->execute([
        'team_id' => $team->getKey(), 'node_id' => $node->getKey(), 'name' => 'deploy', 'type' => 'token', 'secret' => 'secret-value',
    ]);

    $this->actingAs($user);
    app(CredentialInventory::class)->revoke($credential->getKey(), app(RevokeNodeCredential::class));

    expect($credential->fresh()->status->value)->toBe('revoked');
});

it('transitions only a current-team operation task from the Livewire inventory', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $task = app(CreateOperationTask::class)->execute([
        'team_id' => $team->getKey(), 'operation' => 'deploy', 'idempotency_key' => 'task-1',
    ]);

    $this->actingAs($user);
    $component = app(OperationsInventory::class);
    $component->transitionTask($task->getKey(), 'running', app(TransitionOperationTask::class));
    $component->transitionTask($task->getKey(), 'succeeded', app(TransitionOperationTask::class));

    expect(OperationTask::query()->find($task->getKey())->status)->toBe(TaskStatus::Succeeded);
});

it('activates only a current-team database from the Livewire inventory', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $engine = DatabaseEngine::query()->create([
        'id' => (string) Str::uuid(), 'team_id' => $team->getKey(), 'name' => 'MySQL',
        'driver' => 'mysql', 'version' => '8.4', 'host' => 'database.test', 'port' => 3306, 'active' => true,
    ]);
    $database = app(CreateDatabase::class)->execute([
        'team_id' => $team->getKey(), 'engine_id' => $engine->getKey(), 'name' => 'app',
    ]);

    $this->actingAs($user);
    app(DatabaseInventory::class)->activate($database->getKey(), app(ActivateDatabase::class));

    expect(Database::query()->find($database->getKey())->status->value)->toBe('active');
});

it('suspends and revokes only current-team account records from Livewire', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $account = app(CreateAccount::class)->execute([
        'team_id' => $team->getKey(), 'owner_id' => $user->getKey(), 'name' => 'Customer',
    ]);
    $delegation = app(DelegateAccount::class)->execute($account, ['delegate_id' => 'delegate-1']);

    $this->actingAs($user);
    $inventory = app(AccountInventory::class);
    $inventory->suspensionReason = 'Payment review';
    $inventory->suspend($account->getKey(), app(SuspendAccount::class));
    app(AccountFeatureInventory::class)->revokeDelegation($delegation->getKey(), app(RevokeDelegation::class));

    expect(Account::query()->find($account->getKey())->status->value)->toBe('suspended')
        ->and(AccountDelegation::query()->find($delegation->getKey())->active)->toBeFalse();
});
