<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\AccountsServiceProvider;
use Liberu\ControlPanel\Accounts\Actions\CreateAccount;
use Liberu\ControlPanel\Accounts\Actions\CreateHostingPackage;
use Liberu\ControlPanel\Accounts\Actions\DelegateAccount;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Actions\UpdateBranding;
use Liberu\ControlPanel\Accounts\Enums\AccountStatus;
use Liberu\ControlPanel\Accounts\Events\AccountSuspended;
use Liberu\ControlPanel\Accounts\Services\QuotaGuard;

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

it('rejects quota usage above the account limit', function (): void {
    $account = app(CreateAccount::class)->execute([
        'owner_id' => 'user-1',
        'name' => 'Limited customer',
        'quota_overrides' => ['websites' => 2],
    ]);

    expect(fn () => app(QuotaGuard::class)->assertWithinQuota($account, ['websites' => 3]))
        ->toThrow(ValidationException::class);
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
});
