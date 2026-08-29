<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Actions;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Monitoring\Models\MaintenanceWindow;

final class UpdateMaintenanceWindow
{
    /** @param array<string, mixed> $attributes */
    public function execute(MaintenanceWindow $window, array $attributes): MaintenanceWindow
    {
        if (in_array($window->status, ['cancelled', 'completed'], true)) {
            throw ValidationException::withMessages(['maintenance' => 'A terminal maintenance window cannot be updated.']);
        }

        $name = trim((string) ($attributes['name'] ?? $window->name));
        $scope = trim((string) ($attributes['scope'] ?? $window->scope));
        $startsAt = Carbon::parse($attributes['starts_at'] ?? $window->starts_at);
        $endsAt = Carbon::parse($attributes['ends_at'] ?? $window->ends_at);
        if ($name === '' || $scope === '' || $endsAt->lessThanOrEqualTo($startsAt)) {
            throw ValidationException::withMessages(['maintenance' => 'A maintenance name, scope, and valid time range are required.']);
        }

        $window->forceFill(['name' => $name, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'scope' => $scope, 'details' => $attributes['details'] ?? $window->details])->save();

        return $window->refresh();
    }
}
