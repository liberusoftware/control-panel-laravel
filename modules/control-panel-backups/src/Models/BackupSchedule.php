<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BackupSchedule extends Model
{
    use HasUuids;

    protected $table = 'control_panel_backup_schedules';

    protected $fillable = ['team_id', 'policy_id', 'cron', 'timezone', 'active', 'next_run_at', 'last_run_at'];

    protected function casts(): array
    {
        return ['active' => 'bool', 'next_run_at' => 'datetime', 'last_run_at' => 'datetime'];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(BackupPolicy::class, 'policy_id');
    }
}
