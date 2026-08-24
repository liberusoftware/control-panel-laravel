<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Enums\DatabaseStatus;
use Liberu\ControlPanel\Databases\Models\Database;

final class ActivateDatabase
{
    public function execute(Database $database): Database
    {
        if ($database->status === DatabaseStatus::Archived) {
            throw ValidationException::withMessages(['database' => 'Archived databases cannot be activated.']);
        }

        return DB::transaction(function () use ($database): Database {
            $database->update(['status' => DatabaseStatus::Active]);

            return $database->refresh();
        });
    }
}
