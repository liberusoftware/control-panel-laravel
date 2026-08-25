<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Enums\DatabaseStatus;
use Liberu\ControlPanel\Databases\Models\Database;

final class SuspendDatabase
{
    public function execute(Database $database): Database
    {
        if ($database->status === DatabaseStatus::Archived) {
            throw ValidationException::withMessages(['database' => 'An archived database cannot be suspended.']);
        }
        if ($database->status === DatabaseStatus::Suspended) {
            throw ValidationException::withMessages(['database' => 'The database is already suspended.']);
        }

        return DB::transaction(function () use ($database): Database {
            $database->forceFill(['status' => DatabaseStatus::Suspended])->save();

            return $database->refresh();
        });
    }
}
