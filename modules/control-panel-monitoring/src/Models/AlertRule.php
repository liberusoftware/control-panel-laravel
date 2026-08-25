<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;

final class AlertRule extends Model
{
    protected $table = 'control_panel_monitoring_alert_rules';

    protected $fillable = ['id', 'team_id', 'name', 'condition', 'threshold', 'channels', 'active'];

    protected function casts(): array
    {
        return ['threshold' => 'float', 'channels' => 'array', 'active' => 'boolean'];
    }
}
