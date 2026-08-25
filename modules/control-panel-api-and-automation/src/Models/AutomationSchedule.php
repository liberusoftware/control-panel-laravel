<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ApiAutomation\Enums\AutomationStatus;

final class AutomationSchedule extends Model
{
    use HasUuids;

    protected $table = 'control_panel_automation_schedules';

    protected $fillable = ['team_id', 'name', 'cron', 'timezone', 'template_id', 'status', 'next_run_at', 'last_run_at'];

    protected function casts(): array
    {
        return ['status' => AutomationStatus::class, 'next_run_at' => 'datetime', 'last_run_at' => 'datetime'];
    }
}
