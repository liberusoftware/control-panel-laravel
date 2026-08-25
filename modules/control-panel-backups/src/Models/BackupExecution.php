<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Models;

use Illuminate\Database\Eloquent\Model;

final class BackupExecution extends Model
{
    protected $table = 'control_panel_backup_executions';

    protected $fillable = ['id', 'team_id', 'policy_id', 'type', 'consistency', 'status', 'started_at', 'completed_at', 'error', 'metadata'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'metadata' => 'array'];
    }
}
