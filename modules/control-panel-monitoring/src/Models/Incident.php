<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;

final class Incident extends Model
{
    protected $table = 'control_panel_monitoring_incidents';

    protected $fillable = ['id', 'team_id', 'title', 'severity', 'status', 'summary', 'started_at', 'resolved_at', 'metadata'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'resolved_at' => 'datetime', 'metadata' => 'array'];
    }
}
