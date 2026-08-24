<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AutomationDefinition extends Model
{
    use HasUuids;

    protected $table = 'control_panel_automation_definitions';

    protected $fillable = ['team_id', 'name', 'kind', 'status', 'schedule', 'definition', 'credentials'];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['definition' => 'array', 'credentials' => 'encrypted:array'];
    }
}
