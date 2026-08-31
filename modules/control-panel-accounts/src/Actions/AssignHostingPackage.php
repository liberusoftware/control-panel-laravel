<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\Accounts\Models\HostingPackage;
use Liberu\ControlPanel\Accounts\Models\HostingPackageAssignment;

final class AssignHostingPackage
{
    /** @param array<string, mixed> $attributes */
    public function execute(Account $account, HostingPackage $package, array $attributes = []): HostingPackageAssignment
    {
        if ((string) $account->team_id !== (string) $package->team_id) {
            throw ValidationException::withMessages(['hosting_package_id' => 'The hosting package must belong to the account team.']);
        }

        if (! $package->active) {
            throw ValidationException::withMessages(['hosting_package_id' => 'An inactive hosting package cannot be assigned.']);
        }

        $startDate = $this->date($attributes['start_date'] ?? now()->toDateString(), 'start_date');
        $endDate = isset($attributes['end_date']) && $attributes['end_date'] !== null
            ? $this->date($attributes['end_date'], 'end_date')
            : null;
        $this->assertDateOrder($startDate, $endDate);

        return HostingPackageAssignment::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $account->team_id,
            'account_id' => $account->getKey(),
            'hosting_package_id' => $package->getKey(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'active' => $attributes['active'] ?? true,
        ]);
    }

    private function date(mixed $value, string $field): Carbon
    {
        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([$field => 'A valid date is required.']);
        }
    }

    private function assertDateOrder(Carbon $startDate, ?Carbon $endDate): void
    {
        if ($endDate !== null && $endDate->lt($startDate)) {
            throw ValidationException::withMessages(['end_date' => 'The end date must be on or after the start date.']);
        }
    }
}
