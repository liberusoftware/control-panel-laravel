<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class HostingLog extends Model
{
    use HasUuids;
    protected $table = 'control_panel_hosting_logs';
    protected $fillable = ['team_id', 'domain_id', 'kind', 'level', 'message', 'context', 'occurred_at'];
    protected function casts(): array { return ['context' => 'array', 'occurred_at' => 'datetime']; }
}
