<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class IntrusionControl extends Model
{
    use HasUuids;

    protected $table = 'control_panel_intrusion_controls';

    protected $fillable = ['team_id', 'subject_type', 'subject_id', 'kind', 'action', 'threshold', 'window_seconds', 'enabled', 'metadata'];

    protected function casts(): array
    {
        return ['threshold' => 'integer', 'window_seconds' => 'integer', 'enabled' => 'bool', 'metadata' => 'array'];
    }
}
