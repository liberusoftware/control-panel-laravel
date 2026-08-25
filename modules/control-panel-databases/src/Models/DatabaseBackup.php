<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\ControlPanel\Databases\Enums\BackupStatus;

final class DatabaseBackup extends Model
{
    use HasUuids;

    protected $table = 'control_panel_database_backups';

    protected $fillable = ['team_id', 'database_id', 'destination', 'type', 'path', 'size', 'status', 'error', 'started_at', 'completed_at', 'automated'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['status' => BackupStatus::class, 'size' => 'integer', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'automated' => 'bool'];
    }

    public function isCompleted(): bool
    {
        return $this->status === BackupStatus::Completed;
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }
}
