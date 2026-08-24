<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Databases\Enums\UpgradeStatus;

final class DatabaseUpgrade extends Model
{
    use HasUuids;

    protected $table = 'control_panel_database_upgrades';

    protected $fillable = ['team_id', 'database_id', 'from_version', 'to_version', 'status', 'error', 'started_at', 'finished_at', 'metadata'];

    protected function casts(): array
    {
        return ['status' => UpgradeStatus::class, 'started_at' => 'datetime', 'finished_at' => 'datetime', 'metadata' => 'array'];
    }
}
