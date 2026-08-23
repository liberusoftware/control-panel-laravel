<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Enums\AccountStatus;
use Liberu\ControlPanel\Accounts\Enums\AccountType;
use Liberu\ControlPanel\Accounts\Models\Account;

final readonly class CreateAccount
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): Account
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $ownerId = trim((string) ($attributes['owner_id'] ?? ''));
        $type = $attributes['type'] ?? AccountType::Customer;
        $type = $type instanceof AccountType ? $type : AccountType::tryFrom((string) $type);

        if ($name === '' || $ownerId === '' || $type === null) {
            throw ValidationException::withMessages(['account' => 'A valid name, owner, and account type are required.']);
        }

        return DB::transaction(fn (): Account => Account::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $attributes['team_id'] ?? null,
            'parent_id' => $attributes['parent_id'] ?? null,
            'owner_id' => $ownerId,
            'type' => $type,
            'status' => AccountStatus::Active,
            'name' => $name,
            'brand' => $attributes['brand'] ?? [],
            'quota_overrides' => $attributes['quota_overrides'] ?? [],
        ]));
    }
}
