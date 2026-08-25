<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Models\SftpAccount;

final class CreateSftpAccount
{
    public function execute(array $attributes): SftpAccount
    {
        $username = trim((string) ($attributes['username'] ?? ''));
        if ($username === '' || ! preg_match('/^[a-z_][a-z0-9_-]{0,31}$/i', $username)) {
            throw ValidationException::withMessages(['username' => 'The SFTP username is invalid.']);
        }
        if (empty($attributes['password']) && empty($attributes['ssh_public_key'])) {
            throw ValidationException::withMessages(['authentication' => 'A password or SSH public key is required.']);
        }

        return SftpAccount::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'owner_id' => $attributes['owner_id'] ?? null, 'username' => $username, 'password' => $attributes['password'] ?? null, 'home_directory' => $attributes['home_directory'] ?? "/home/{$username}", 'quota_mb' => max((int) ($attributes['quota_mb'] ?? 0), 0), 'bandwidth_limit_mb' => max((int) ($attributes['bandwidth_limit_mb'] ?? 0), 0), 'active' => true, 'ssh_key_auth_enabled' => ! empty($attributes['ssh_public_key']), 'ssh_public_key' => $attributes['ssh_public_key'] ?? null, 'ssh_private_key' => $attributes['ssh_private_key'] ?? null, 'ssh_key_type' => $attributes['ssh_key_type'] ?? null, 'ssh_key_bits' => $attributes['ssh_key_bits'] ?? null]);
    }
}
