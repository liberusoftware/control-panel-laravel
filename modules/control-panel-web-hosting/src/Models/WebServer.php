<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class WebServer extends Model
{
    use HasUuids;

    protected $table = 'control_panel_web_servers';

    protected $fillable = ['team_id', 'node_id', 'server', 'version', 'status', 'config', 'metadata'];

    protected function casts(): array
    {
        return ['config' => 'array', 'metadata' => 'array'];
    }
}
