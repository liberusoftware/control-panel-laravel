<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Backups\Enums\RestoreStatus;

final class BackupRestore extends Model
{
    use HasUuids;
    protected $table = 'control_panel_backup_restores';
    protected $fillable = ['team_id', 'snapshot_id', 'target', 'status', 'options', 'error', 'started_at', 'finished_at'];
    protected function casts(): array { return ['status' => RestoreStatus::class, 'options' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime']; }
}
