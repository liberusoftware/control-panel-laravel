<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Fail2banSetting extends Model
{
    use HasUuids;

    protected $table = 'control_panel_fail2ban_settings';

    protected $fillable = ['team_id', 'jail_name', 'enabled', 'max_retry', 'find_time', 'ban_time', 'whitelist_ips'];

    protected function casts(): array
    {
        return ['enabled' => 'bool', 'max_retry' => 'integer', 'find_time' => 'integer', 'ban_time' => 'integer', 'whitelist_ips' => 'array'];
    }

    public function bans(): HasMany
    {
        return $this->hasMany(Fail2banBan::class, 'jail_name', 'jail_name')->where('team_id', $this->team_id);
    }
}
