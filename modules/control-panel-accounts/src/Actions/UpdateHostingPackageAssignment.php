<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Models\HostingPackageAssignment;

final class UpdateHostingPackageAssignment
{
    /** @param array<string, mixed> $attributes */
    public function execute(HostingPackageAssignment $assignment, array $attributes): HostingPackageAssignment
    {
        $startDate = array_key_exists('start_date', $attributes)
            ? $this->date($attributes['start_date'], 'start_date')
            : Carbon::parse($assignment->start_date)->startOfDay();
        $endDate = array_key_exists('end_date', $attributes) && $attributes['end_date'] !== null
            ? $this->date($attributes['end_date'], 'end_date')
            : (array_key_exists('end_date', $attributes) ? null : ($assignment->end_date ? Carbon::parse($assignment->end_date)->startOfDay() : null));

        if ($endDate !== null && $endDate->lt($startDate)) {
            throw ValidationException::withMessages(['end_date' => 'The end date must be on or after the start date.']);
        }

        $assignment->forceFill([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'active' => $attributes['active'] ?? $assignment->active,
        ])->save();

        return $assignment->refresh();
    }

    private function date(mixed $value, string $field): Carbon
    {
        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([$field => 'A valid date is required.']);
        }
    }
}
