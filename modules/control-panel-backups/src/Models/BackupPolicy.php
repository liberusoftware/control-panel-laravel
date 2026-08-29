<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BackupPolicy extends Model
{
    use HasUuids;

    protected $table = 'control_panel_backup_policies';

    protected $fillable = ['team_id', 'name', 'schedule', 'retention_days', 'storage_driver', 'storage_config', 'encrypted', 'active'];

    protected $hidden = ['storage_config'];

    protected function casts(): array
    {
        return ['schedule' => 'array', 'storage_config' => 'encrypted:array', 'retention_days' => 'integer', 'encrypted' => 'bool', 'active' => 'bool'];
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(BackupSnapshot::class, 'policy_id');
    }
}
