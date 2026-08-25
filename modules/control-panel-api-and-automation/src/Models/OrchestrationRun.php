<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ApiAutomation\Enums\AutomationStatus;

final class OrchestrationRun extends Model
{
    use HasUuids;

    protected $table = 'control_panel_orchestration_runs';

    protected $fillable = ['team_id', 'template_id', 'schedule_id', 'status', 'input', 'output', 'error', 'started_at', 'finished_at', 'idempotency_key'];

    protected function casts(): array
    {
        return ['status' => AutomationStatus::class, 'input' => 'array', 'output' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}
