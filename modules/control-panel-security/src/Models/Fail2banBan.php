<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Fail2banBan extends Model
{
    use HasUuids;

    protected $table = 'control_panel_fail2ban_bans';

    protected $fillable = ['team_id', 'jail_name', 'ip_address', 'banned_at', 'unbanned_at', 'ban_count', 'reason'];

    protected function casts(): array
    {
        return ['banned_at' => 'datetime', 'unbanned_at' => 'datetime', 'ban_count' => 'integer'];
    }

    public function isActive(): bool
    {
        return $this->unbanned_at === null || $this->unbanned_at->isFuture();
    }
}
