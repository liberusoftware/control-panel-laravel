<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Accounts\Actions\CreateAccount;
use Liberu\ControlPanel\Accounts\Models\Account;

final class AccountController
{
    public function index(Request $request): JsonResponse
    {
        $accounts = Account::query()
            ->where('team_id', $request->user()?->current_team_id)
            ->latest()
            ->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json([
            'data' => $accounts->through(static fn (Account $account): array => [
                'id' => $account->getKey(),
                'type' => 'control-panel-account',
                'attributes' => $account->only(['name', 'type', 'status', 'brand', 'quota_overrides']),
            ]),
            'meta' => ['current_page' => $accounts->currentPage(), 'per_page' => $accounts->perPage(), 'total' => $accounts->total()],
        ]);
    }

    public function store(Request $request, CreateAccount $create): JsonResponse
    {
        $data = $request->validate([
            'owner_id' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:customer,reseller,administrator'],
            'name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'array'],
            'quota_overrides' => ['nullable', 'array'],
        ]);

        $account = $create->execute(array_merge($data, ['team_id' => $request->user()?->current_team_id]));

        return response()->json(['data' => [
            'id' => $account->getKey(),
            'type' => 'control-panel-account',
            'attributes' => $account->only(['name', 'type', 'status', 'brand', 'quota_overrides']),
        ]], 201);
    }
}
