<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AutomationCommand extends Model
{
    use HasUuids;
    protected $table = 'control_panel_automation_commands';
    protected $fillable = ['team_id', 'name', 'description', 'command', 'arguments', 'enabled', 'last_run_at'];
    protected function casts(): array { return ['arguments' => 'array', 'enabled' => 'bool', 'last_run_at' => 'datetime']; }
}
