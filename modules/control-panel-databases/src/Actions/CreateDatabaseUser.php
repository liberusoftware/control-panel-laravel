<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseUser;

final class CreateDatabaseUser
{
    /** @param array<string, mixed> $attributes */
    public function execute(Database $database, array $attributes): DatabaseUser
    {
        $username = trim((string) ($attributes['username'] ?? ''));
        $password = (string) ($attributes['password'] ?? '');
        if ($username === '' || mb_strlen($password) < 16) {
            throw ValidationException::withMessages(['credentials' => 'A username and password of at least 16 characters are required.']);
        }

        return DatabaseUser::query()->create(['id' => (string) Str::uuid(), 'team_id' => $database->team_id, 'database_id' => $database->getKey(), 'username' => $username, 'host' => $attributes['host'] ?? '%', 'password' => $password, 'active' => true]);
    }
}
