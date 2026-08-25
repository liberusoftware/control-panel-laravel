<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class BackupDestination extends Model
{
    use HasUuids;
    protected $table = 'control_panel_backup_destinations';
    protected $fillable = ['team_id', 'name', 'driver', 'config', 'retention_days', 'default', 'active', 'last_checked_at', 'health'];
    protected $hidden = ['config'];
    protected function casts(): array { return ['config' => 'encrypted:array', 'retention_days' => 'integer', 'default' => 'bool', 'active' => 'bool', 'last_checked_at' => 'datetime', 'health' => 'array']; }
}
