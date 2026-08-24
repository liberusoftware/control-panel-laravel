<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Monitor extends Model
{
    use HasUuids;

    protected $table = 'control_panel_monitors';

    protected $fillable = ['team_id', 'subject_type', 'subject_id', 'name', 'status', 'last_checked_at', 'metrics'];

    protected function casts(): array
    {
        return ['last_checked_at' => 'datetime', 'metrics' => 'array'];
    }
}
