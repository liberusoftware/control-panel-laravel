<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OsService extends Model
{
    use HasUuids;

    protected $table = 'control_panel_os_services';

    protected $fillable = ['team_id', 'node_id', 'name', 'version', 'status', 'enabled', 'metadata'];

    protected function casts(): array
    {
        return ['enabled' => 'bool', 'metadata' => 'array'];
    }
}
