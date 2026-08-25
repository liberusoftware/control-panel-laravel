<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApplicationMetric extends Model
{
    use HasUuids;

    protected $table = 'control_panel_application_metrics';

    protected $fillable = ['id', 'team_id', 'application_id', 'response_time_ms', 'status_code', 'healthy', 'checked_at', 'details'];

    protected function casts(): array
    {
        return ['response_time_ms' => 'integer', 'status_code' => 'integer', 'healthy' => 'boolean', 'checked_at' => 'datetime', 'details' => 'array'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(HostedApplication::class, 'application_id');
    }
}
