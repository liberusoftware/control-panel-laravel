<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OsAdapter extends Model
{
    use HasUuids;

    protected $table = 'control_panel_os_adapters';

    protected $fillable = ['team_id', 'node_id', 'operating_system', 'version', 'capabilities', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['capabilities' => 'array', 'metadata' => 'array'];
    }
}
