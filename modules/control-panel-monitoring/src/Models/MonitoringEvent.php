<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MonitoringEvent extends Model
{
    use HasUuids;

    protected $table = 'control_panel_monitoring_events';

    protected $fillable = ['id', 'team_id', 'monitor_id', 'kind', 'status', 'payload', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
