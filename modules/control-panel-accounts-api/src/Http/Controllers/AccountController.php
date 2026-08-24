<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Accounts\Actions\CreateAccount;
use Liberu\ControlPanel\Accounts\Actions\DelegateAccount;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Actions\UpdateBranding;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\Accounts\Queries\ListAccounts;

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

    public function delegate(Request $request, Account $account, DelegateAccount $delegate): JsonResponse
    {
        abort_unless((string) $account->team_id === (string) $request->user()?->current_team_id, 404);
        $data = $request->validate(['delegate_id' => ['required', 'string', 'max:255'], 'permissions' => ['nullable', 'array'], 'expires_at' => ['nullable', 'date']]);

        return response()->json(['data' => $delegate->execute($account, $data)], 201);
    }

    public function branding(Request $request, Account $account, UpdateBranding $update): JsonResponse
    {
        abort_unless((string) $account->team_id === (string) $request->user()?->current_team_id, 404);
        $data = $request->validate(['logo_url' => ['nullable', 'url', 'max:2048'], 'name' => ['nullable', 'string', 'max:160'], 'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/']]);

        return response()->json(['data' => self::resource($update->execute($account, $data))]);
    }

    public function store(Request $request, CreateAccount $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate([
            'owner_id' => ['required', 'string', 'max:255'],
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
}
