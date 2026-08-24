<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class DatabaseHealthCheck extends Model
{
    use HasUuids;

    protected $table = 'control_panel_database_health_checks';

    protected $fillable = ['team_id', 'database_id', 'healthy', 'latency_ms', 'message', 'details', 'checked_at'];

    protected function casts(): array
    {
        return ['healthy' => 'bool', 'latency_ms' => 'integer', 'details' => 'array', 'checked_at' => 'datetime'];
    }
}
