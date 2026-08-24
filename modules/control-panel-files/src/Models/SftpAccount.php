<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Models;

use Illuminate\Database\Eloquent\Model;

final class SftpAccount extends Model
{
    protected $table = 'control_panel_file_sftp_accounts';

    protected $fillable = ['id', 'team_id', 'owner_id', 'username', 'password', 'home_directory', 'quota_mb', 'bandwidth_limit_mb', 'active', 'last_login_at', 'ssh_key_auth_enabled', 'ssh_public_key', 'ssh_private_key', 'ssh_key_type', 'ssh_key_bits'];

    protected $hidden = ['password', 'ssh_private_key'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'ssh_private_key' => 'encrypted', 'quota_mb' => 'integer', 'bandwidth_limit_mb' => 'integer', 'active' => 'boolean', 'ssh_key_auth_enabled' => 'boolean', 'ssh_key_bits' => 'integer', 'last_login_at' => 'datetime'];
    }
}
