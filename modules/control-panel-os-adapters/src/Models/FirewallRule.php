<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class FirewallRule extends Model
{
    use HasUuids;

    protected $table = 'control_panel_firewall_rules';

    protected $fillable = ['team_id', 'node_id', 'direction', 'action', 'protocol', 'port', 'source', 'comment', 'active'];

    protected function casts(): array
    {
        return ['port' => 'integer', 'active' => 'bool'];
    }
}
