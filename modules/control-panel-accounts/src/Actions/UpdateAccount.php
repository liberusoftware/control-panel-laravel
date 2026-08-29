<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Enums\AccountType;
use Liberu\ControlPanel\Accounts\Models\Account;

final class UpdateAccount
{
    /** @param array<string, mixed> $attributes */
    public function execute(Account $account, array $attributes): Account
    {
        $name = trim((string) ($attributes['name'] ?? $account->name));
        $ownerId = trim((string) ($attributes['owner_id'] ?? $account->owner_id));
        $type = $attributes['type'] ?? $account->type;
        $type = $type instanceof AccountType ? $type : AccountType::tryFrom((string) $type);

        if ($name === '' || $ownerId === '' || $type === null) {
            throw ValidationException::withMessages(['account' => 'A valid name, owner, and account type are required.']);
        }

        $parentId = array_key_exists('parent_id', $attributes) ? $attributes['parent_id'] : $account->parent_id;
        if ($parentId !== null) {
            $parent = Account::query()->whereKey($parentId)->where('team_id', $account->team_id)->first();
            $validParent = $parent !== null && (string) $parent->getKey() !== (string) $account->getKey()
                && (($type === AccountType::Customer && in_array($parent->type, [AccountType::Reseller, AccountType::Administrator], true))
                    || ($type === AccountType::Reseller && $parent->type === AccountType::Administrator));
            if (! $validParent) {
                throw ValidationException::withMessages(['parent_id' => 'The selected parent cannot own this account type.']);
            }
        }

        $account->forceFill([
            'parent_id' => $parentId,
            'owner_id' => $ownerId,
            'type' => $type,
            'name' => $name,
            'brand' => $attributes['brand'] ?? $account->brand,
            'quota_overrides' => $attributes['quota_overrides'] ?? $account->quota_overrides,
        ])->save();

        return $account->refresh();
    }
}
