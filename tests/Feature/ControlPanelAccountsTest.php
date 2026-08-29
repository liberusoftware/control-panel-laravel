<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\AccountsServiceProvider;
use Liberu\ControlPanel\Accounts\Actions\ArchiveAccount;
use Liberu\ControlPanel\Accounts\Actions\CreateAccount;
use Liberu\ControlPanel\Accounts\Actions\CreateHostingPackage;
use Liberu\ControlPanel\Accounts\Actions\DelegateAccount;
use Liberu\ControlPanel\Accounts\Actions\RevokeDelegation;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Actions\UpdateAccount;
use Liberu\ControlPanel\Accounts\Actions\UpdateBranding;
use Liberu\ControlPanel\Accounts\Actions\UpdateDelegation;
use Liberu\ControlPanel\Accounts\Actions\UpdateHostingPackage;
use Liberu\ControlPanel\Accounts\Enums\AccountStatus;
use Liberu\ControlPanel\Accounts\Enums\AccountType;
use Liberu\ControlPanel\Accounts\Events\AccountSuspended;
use Liberu\ControlPanel\Accounts\Services\QuotaGuard;
use Liberu\ControlPanel\AccountsApi\AccountsApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(AccountsServiceProvider::class);
    $this->artisan('migrate');
});

it('creates an account with an active status and opaque owner reference', function (): void {
    $account = app(CreateAccount::class)->execute([
        'team_id' => 'team-1',
        'owner_id' => 'user-1',
        'type' => 'reseller',
        'name' => 'Example reseller',
        'brand' => ['name' => 'Example Hosting'],
        'quota_overrides' => ['websites' => 10],
    ]);

    expect($account->status)->toBe(AccountStatus::Active)
        ->and($account->owner_id)->toBe('user-1')
        ->and(DB::table('control_panel_accounts')->where('id', $account->getKey())->exists())->toBeTrue();
});

it('suspends an account with a reason and emits an after-commit event', function (): void {
    Event::fake();
    $account = app(CreateAccount::class)->execute(['owner_id' => 'user-1', 'name' => 'Customer']);

    $suspended = app(SuspendAccount::class)->execute($account, 'Payment review');

    expect($suspended->status)->toBe(AccountStatus::Suspended)
        ->and($suspended->suspended_reason)->toBe('Payment review')
        ->and($suspended->suspended_at)->not->toBeNull();
    Event::assertDispatched(AccountSuspended::class);
});

it('archives an account and rejects repeating the transition', function (): void {
    $account = app(CreateAccount::class)->execute(['owner_id' => 'user-1', 'name' => 'Customer']);

    $archived = app(ArchiveAccount::class)->execute($account);

    expect($archived->status)->toBe(AccountStatus::Archived)
        ->and(fn () => app(ArchiveAccount::class)->execute($archived))
        ->toThrow(ValidationException::class);
});

it('rejects quota usage above the account limit', function (): void {
    $account = app(CreateAccount::class)->execute([
        'owner_id' => 'user-1',
        'name' => 'Limited customer',
        'quota_overrides' => ['websites' => 2],
    ]);

    expect(fn () => app(QuotaGuard::class)->assertWithinQuota($account, ['websites' => 3]))
        ->toThrow(ValidationException::class);
});

it('enforces account hierarchy when a parent is provided', function (): void {
    $administrator = app(CreateAccount::class)->execute([
        'team_id' => 'team-1', 'owner_id' => 'owner-admin', 'type' => AccountType::Administrator, 'name' => 'Administrator',
    ]);
    $customer = app(CreateAccount::class)->execute([
        'team_id' => 'team-1', 'owner_id' => 'owner-customer', 'type' => AccountType::Customer, 'name' => 'Customer', 'parent_id' => $administrator->getKey(),
    ]);

    expect($customer->parent_id)->toBe($administrator->getKey());
    expect(fn () => app(CreateAccount::class)->execute([
        'team_id' => 'team-1', 'owner_id' => 'owner-admin', 'type' => AccountType::Administrator, 'name' => 'Nested administrator', 'parent_id' => $customer->getKey(),
    ]))->toThrow(ValidationException::class);
});

it('supports packages, delegation, and validated branding', function (): void {
    $account = app(CreateAccount::class)->execute(['team_id' => 'team-1', 'owner_id' => 'user-1', 'name' => 'Customer']);
    $package = app(CreateHostingPackage::class)->execute(['team_id' => 'team-1', 'name' => 'Starter', 'limits' => ['sites' => 1]]);
    $delegation = app(DelegateAccount::class)->execute($account, ['delegate_id' => 'user-2', 'permissions' => ['view' => true]]);
    $updated = app(UpdateBranding::class)->execute($account, ['name' => 'Customer Brand', 'primary_color' => '#336699']);

    expect($package->limits)->toMatchArray(['sites' => 1])
        ->and($delegation->delegate_id)->toBe('user-2')
        ->and($updated->brand)->toMatchArray(['primary_color' => '#336699']);
    expect(fn () => app(UpdateBranding::class)->execute($account, ['logo_url' => 'not-a-url']))
        ->toThrow(ValidationException::class);

    $package = app(UpdateHostingPackage::class)->execute($package, ['active' => false]);
    $delegation = app(RevokeDelegation::class)->execute($delegation);
    expect($package->active)->toBeFalse()->and($delegation->active)->toBeFalse();

    $delegation = app(DelegateAccount::class)->execute($account, ['delegate_id' => 'user-3']);
    expect(app(UpdateDelegation::class)->execute($delegation, ['permissions' => ['manage' => true]])->permissions)
        ->toMatchArray(['manage' => true]);
});

it('updates accounts through the domain action while preserving hierarchy invariants', function (): void {
    $parent = app(CreateAccount::class)->execute(['team_id' => 'team-1', 'owner_id' => 'owner-parent', 'type' => 'reseller', 'name' => 'Parent']);
    $account = app(CreateAccount::class)->execute(['team_id' => 'team-1', 'owner_id' => 'owner-child', 'type' => 'customer', 'name' => 'Child', 'parent_id' => $parent->getKey()]);

    $updated = app(UpdateAccount::class)->execute($account, ['name' => 'Renamed child', 'brand' => ['name' => 'Brand']]);

    expect($updated->name)->toBe('Renamed child')->and($updated->brand)->toMatchArray(['name' => 'Brand']);
    expect(fn () => app(UpdateAccount::class)->execute($account, ['parent_id' => $account->getKey()]))
        ->toThrow(ValidationException::class);
});

it('exposes the quota guard through the authenticated tenant-scoped API', function (): void {
    app()->register(AccountsApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $account = app(CreateAccount::class)->execute([
        'team_id' => $team->getKey(), 'owner_id' => $user->getKey(), 'name' => 'API customer',
        'quota_overrides' => ['websites' => 3],
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/accounts/'.$account->getKey().'/quota-check', ['usage' => ['websites' => 2]])
        ->assertOk()
        ->assertJsonPath('data.attributes.within_quota', true);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/accounts/'.$account->getKey().'/quota-check', ['usage' => ['websites' => 4]])
        ->assertUnprocessable();
});

it('bounds hosting package pagination for the authenticated tenant API', function (): void {
    app()->register(AccountsApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    app(CreateHostingPackage::class)->execute(['team_id' => $team->getKey(), 'name' => 'Starter']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/accounts/packages?per_page=1000')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('exposes individual accounts only to their current team', function (): void {
    app()->register(AccountsApiServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $account = app(CreateAccount::class)->execute([
        'team_id' => $team->getKey(), 'owner_id' => $user->getKey(), 'name' => 'Visible account',
    ]);
    $otherAccount = app(CreateAccount::class)->execute([
        'team_id' => $otherTeam->getKey(), 'owner_id' => $user->getKey(), 'name' => 'Hidden account',
    ]);

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/control-panel/accounts/'.$account->getKey())
        ->assertOk()->assertJsonPath('data.attributes.name', 'Visible account');

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/control-panel/accounts/'.$otherAccount->getKey())
        ->assertNotFound();
});

it('updates only a current-team account through the API', function (): void {
    app()->register(AccountsApiServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $account = app(CreateAccount::class)->execute(['team_id' => $team->getKey(), 'owner_id' => 'owner-1', 'name' => 'Editable account']);
    $otherAccount = app(CreateAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'owner_id' => 'owner-2', 'name' => 'Other account']);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/control-panel/accounts/'.$account->getKey(), ['name' => 'Updated account', 'owner_id' => 'owner-updated'])
        ->assertOk()
        ->assertJsonPath('data.attributes.name', 'Updated account')
        ->assertJsonPath('data.attributes.owner_id', 'owner-updated');

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/control-panel/accounts/'.$otherAccount->getKey(), ['name' => 'Should not update'])
        ->assertNotFound();
});

it('archives an account through the tenant-scoped API', function (): void {
    app()->register(AccountsApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $account = app(CreateAccount::class)->execute([
        'team_id' => $team->getKey(), 'owner_id' => $user->getKey(), 'name' => 'Archive customer',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/accounts/'.$account->getKey().'/archive')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'archived');
});

it('updates only a current-team delegation through the API', function (): void {
    app()->register(AccountsApiServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $account = app(CreateAccount::class)->execute(['team_id' => $team->getKey(), 'owner_id' => 'owner-1', 'name' => 'Delegated account']);
    $delegation = app(DelegateAccount::class)->execute($account, ['delegate_id' => 'delegate-1']);
    $otherAccount = app(CreateAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'owner_id' => 'owner-2', 'name' => 'Other account']);
    $otherDelegation = app(DelegateAccount::class)->execute($otherAccount, ['delegate_id' => 'delegate-2']);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/control-panel/accounts/delegations/'.$delegation->getKey(), ['permissions' => ['manage' => true]])
        ->assertOk()
        ->assertJsonPath('data.attributes.permissions.manage', true);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/control-panel/accounts/delegations/'.$otherDelegation->getKey(), ['permissions' => ['manage' => true]])
        ->assertNotFound();
});

it('rejects delegation revocation without a current team', function (): void {
    app()->register(AccountsApiServiceProvider::class);
    $user = User::factory()->create(['current_team_id' => null]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/accounts/delegations/'.Str::uuid().'/revoke')
        ->assertForbidden();
});
