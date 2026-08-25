<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Accounts\Actions\CreateAccount;
use Liberu\ControlPanel\Accounts\Actions\DelegateAccount;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Actions\UpdateBranding;
use Liberu\ControlPanel\Accounts\Actions\ActivateAccount;
use Liberu\ControlPanel\Accounts\Actions\CreateHostingPackage;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\Accounts\Models\HostingPackage;
use Liberu\ControlPanel\Accounts\Models\AccountDelegation;
use Liberu\ControlPanel\Accounts\Queries\ListAccounts;
use Liberu\ControlPanel\Accounts\Actions\UpdateHostingPackage;
use Liberu\ControlPanel\Accounts\Actions\RevokeDelegation;
use Liberu\ControlPanel\Accounts\Services\QuotaGuard;

final class AccountController
{
    public function index(Request $request, ListAccounts $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $accounts = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json([
            'data' => $accounts->through(static fn (Account $account): array => [
                'id' => $account->getKey(),
                'type' => 'control-panel-account',
                'attributes' => $account->only(['name', 'type', 'status', 'brand', 'quota_overrides']),
            ]),
            'meta' => ['current_page' => $accounts->currentPage(), 'per_page' => $accounts->perPage(), 'total' => $accounts->total()],
        ]);
    }

    public function suspend(Request $request, Account $account, SuspendAccount $suspend): JsonResponse
    {
        abort_unless((string) $account->team_id === (string) $request->user()?->current_team_id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $account = $suspend->execute($account, $data['reason']);

        return response()->json(['data' => self::resource($account)]);
    }

    public function activate(Request $request, Account $account, ActivateAccount $activate): JsonResponse
    {
        $this->assertTeam($request, $account);
        return response()->json(['data' => self::resource($activate->execute($account))]);
    }

    public function package(Request $request, CreateHostingPackage $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'limits' => ['nullable', 'array'], 'features' => ['nullable', 'array'], 'active' => ['sometimes', 'boolean']]);
        $package = $create->execute(array_merge($data, ['team_id' => $teamId]));
        return response()->json(['data' => ['id' => $package->getKey(), 'type' => 'control-panel-hosting-package', 'attributes' => $package->only(['name', 'limits', 'features', 'active'])]], 201);
    }

    public function packages(Request $request): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = HostingPackage::query()->where('team_id', $teamId)->latest()->paginate($request->integer('per_page', 25));
        return response()->json(['data' => $items->through(static fn (HostingPackage $item): array => ['id' => $item->getKey(), 'type' => 'control-panel-hosting-package', 'attributes' => $item->only(['name', 'limits', 'features', 'active'])]), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function updatePackage(Request $request, string $package, UpdateHostingPackage $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        $item = HostingPackage::query()->whereKey($package)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:160'], 'limits' => ['sometimes', 'array'], 'features' => ['sometimes', 'array'], 'active' => ['sometimes', 'boolean']]);
        $item = $update->execute($item, $data);
        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-hosting-package', 'attributes' => $item->only(['name', 'limits', 'features', 'active'])]]);
    }

    public function delegate(Request $request, Account $account, DelegateAccount $delegate): JsonResponse
    {
        abort_unless((string) $account->team_id === (string) $request->user()?->current_team_id, 404);
        $data = $request->validate(['delegate_id' => ['required', 'string', 'max:255'], 'permissions' => ['nullable', 'array'], 'expires_at' => ['nullable', 'date']]);

        return response()->json(['data' => $delegate->execute($account, $data)], 201);
    }

    public function delegations(Request $request, Account $account): JsonResponse
    {
        $this->assertTeam($request, $account);
        $items = AccountDelegation::query()->where('account_id', $account->getKey())->latest()->get();
        return response()->json(['data' => $items->map(static fn (AccountDelegation $item): array => ['id' => $item->getKey(), 'type' => 'control-panel-account-delegation', 'attributes' => $item->only(['delegate_id', 'permissions', 'expires_at', 'active'])])]);
    }

    public function revokeDelegation(Request $request, string $delegation, RevokeDelegation $revoke): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        $item = AccountDelegation::query()->whereKey($delegation)->where('team_id', $teamId)->firstOrFail();
        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-account-delegation', 'attributes' => $revoke->execute($item)->only(['delegate_id', 'permissions', 'expires_at', 'active'])]]);
    }

    public function branding(Request $request, Account $account, UpdateBranding $update): JsonResponse
    {
        abort_unless((string) $account->team_id === (string) $request->user()?->current_team_id, 404);
        $data = $request->validate(['logo_url' => ['nullable', 'url', 'max:2048'], 'name' => ['nullable', 'string', 'max:160'], 'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/']]);

        return response()->json(['data' => self::resource($update->execute($account, $data))]);
    }

    public function quotaCheck(Request $request, Account $account, QuotaGuard $quotas): JsonResponse
    {
        $this->assertTeam($request, $account);
        $data = $request->validate(['usage' => ['required', 'array'], 'usage.*' => ['integer', 'min:0']]);
        $quotas->assertWithinQuota($account, $data['usage']);

        return response()->json(['data' => ['id' => $account->getKey(), 'type' => 'control-panel-account-quota', 'attributes' => ['within_quota' => true, 'usage' => $data['usage'], 'limits' => $account->quota_overrides ?? []]]]);
    }

    public function store(Request $request, CreateAccount $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate([
            'owner_id' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'uuid'],
            'type' => ['required', 'in:customer,reseller,administrator'],
            'name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'array'],
            'quota_overrides' => ['nullable', 'array'],
        ]);

        $account = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($account)], 201);
    }

    private static function resource(Account $account): array
    {
        return ['id' => $account->getKey(), 'type' => 'control-panel-account', 'attributes' => $account->only(['name', 'type', 'status', 'brand', 'quota_overrides', 'suspended_reason', 'suspended_at'])];
    }

    private function assertTeam(Request $request, Account $account): void
    {
        abort_unless((string) $account->team_id === (string) $request->user()?->current_team_id, 404);
    }
}
