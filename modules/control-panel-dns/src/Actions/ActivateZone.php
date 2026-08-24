<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Enums\ZoneStatus;
use Liberu\ControlPanel\Dns\Models\Zone;

final class ActivateZone
{
    public function execute(Zone $zone): Zone
    {
        if ($zone->status === ZoneStatus::Archived) {
            throw ValidationException::withMessages(['zone' => 'Archived zones cannot be activated.']);
        }

        return DB::transaction(function () use ($zone): Zone {
            $zone->update(['status' => ZoneStatus::Active]);

            return $zone->refresh();
        });
    }
}
