<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Monitoring\Models\MaintenanceWindow;

final class DeleteMaintenanceWindow
{
    public function execute(MaintenanceWindow $window): void
    {
        if (in_array($window->status, ['active', 'completed'], true)) {
            throw ValidationException::withMessages(['maintenance' => 'This maintenance window cannot be deleted.']);
        }

        $window->delete();
    }
}
