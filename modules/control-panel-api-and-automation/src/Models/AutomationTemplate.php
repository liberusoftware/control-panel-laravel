<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AutomationTemplate extends Model
{
    use HasUuids;

    protected $table = 'control_panel_automation_templates';

    protected $fillable = ['team_id', 'name', 'version', 'description', 'inputs', 'steps', 'active'];

    protected function casts(): array
    {
        return ['inputs' => 'array', 'steps' => 'array', 'active' => 'bool'];
    }
}
