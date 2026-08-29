<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Monitoring\Models\MaintenanceWindow;

final class CancelMaintenanceWindow
{
    public function execute(MaintenanceWindow $window): MaintenanceWindow
    {
        if (in_array($window->status, ['cancelled', 'completed'], true)) {
            throw ValidationException::withMessages(['maintenance' => 'This maintenance window cannot be cancelled.']);
        }

        return DB::transaction(function () use ($window): MaintenanceWindow {
            $window->forceFill(['status' => 'cancelled'])->save();

            return $window->refresh();
        });
    }
}
