<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Models\DatabasePrivilege;
use Liberu\ControlPanel\Databases\Models\DatabaseUser;

final class GrantDatabasePrivilege
{
    private const ALLOWED = ['select', 'insert', 'update', 'delete', 'create', 'alter', 'index', 'execute'];

    public function execute(DatabaseUser $user, string $privilege, string $objectName = '*'): DatabasePrivilege
    {
        $privilege = mb_strtolower(trim($privilege));
        $objectName = trim($objectName);
        if (! in_array($privilege, self::ALLOWED, true) || $objectName === '' || preg_match('/[^a-zA-Z0-9_.*:-]/', $objectName)) {
            throw ValidationException::withMessages(['privilege' => 'The privilege or object name is not allowed.']);
        }

        return DatabasePrivilege::query()->firstOrCreate(['database_user_id' => $user->getKey(), 'privilege' => $privilege, 'object_name' => $objectName], ['id' => (string) Str::uuid(), 'team_id' => $user->team_id, 'database_id' => $user->database_id]);
    }
}
