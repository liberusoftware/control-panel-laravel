<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;

final class MetricSample extends Model
{
    protected $table = 'control_panel_monitoring_metrics';

    protected $fillable = ['id', 'team_id', 'monitor_id', 'name', 'value', 'unit', 'dimensions', 'sampled_at'];

    protected function casts(): array
    {
        return ['value' => 'float', 'dimensions' => 'array', 'sampled_at' => 'datetime'];
    }
}
