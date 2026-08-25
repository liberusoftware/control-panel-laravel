<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Enums\DatabaseStatus;
use Liberu\ControlPanel\Databases\Models\Database;

final class ArchiveDatabase
{
    public function execute(Database $database): Database
    {
        if ($database->status === DatabaseStatus::Archived) {
            throw ValidationException::withMessages(['database' => 'The database is already archived.']);
        }

        return DB::transaction(function () use ($database): Database {
            $database->forceFill(['status' => DatabaseStatus::Archived])->save();

            return $database->refresh();
        });
    }
}
