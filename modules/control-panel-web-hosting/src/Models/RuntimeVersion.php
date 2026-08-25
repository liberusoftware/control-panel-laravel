<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class RuntimeVersion extends Model
{
    use HasUuids;

    protected $table = 'control_panel_runtime_versions';

    protected $fillable = ['team_id', 'runtime', 'version', 'available', 'default', 'metadata'];

    protected function casts(): array
    {
        return ['available' => 'bool', 'default' => 'bool', 'metadata' => 'array'];
    }
}
