<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\DirectoryProtection;
use Liberu\ControlPanel\WebHosting\Models\DirectoryProtectionUser;

final class AddDirectoryProtectionUser
{
    public function execute(DirectoryProtection $protection, string $username, string $password): DirectoryProtectionUser
    {
        $username = trim($username);
        if ($username === '' || ! preg_match('/^[A-Za-z0-9._-]{1,120}$/', $username)) {
            throw ValidationException::withMessages(['username' => 'The username contains invalid characters.']);
        }
        if (mb_strlen($password) < 8) {
            throw ValidationException::withMessages(['password' => 'The password must contain at least 8 characters.']);
        }

        return DirectoryProtectionUser::query()->updateOrCreate(
            ['directory_protection_id' => $protection->getKey(), 'username' => $username],
            ['id' => (string) Str::uuid(), 'team_id' => $protection->team_id, 'password' => $password],
        );
    }
}
