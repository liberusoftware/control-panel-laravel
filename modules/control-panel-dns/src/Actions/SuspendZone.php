<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Enums\ZoneStatus;
use Liberu\ControlPanel\Dns\Models\Zone;

final class SuspendZone
{
    public function execute(Zone $zone): Zone
    {
        if ($zone->status === ZoneStatus::Archived) {
            throw ValidationException::withMessages(['zone' => 'An archived zone cannot be suspended.']);
        }
        if ($zone->status === ZoneStatus::Suspended) {
            throw ValidationException::withMessages(['zone' => 'The zone is already suspended.']);
        }

        return DB::transaction(function () use ($zone): Zone {
            $zone->forceFill(['status' => ZoneStatus::Suspended])->save();

            return $zone->refresh();
        });
    }
}
