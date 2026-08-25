<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Models;

use Illuminate\Database\Eloquent\Model;

final class DnsCheck extends Model
{
    protected $table = 'control_panel_dns_checks';

    protected $fillable = ['id', 'team_id', 'zone_id', 'kind', 'status', 'result', 'checked_at'];

    protected function casts(): array
    {
        return ['result' => 'array', 'checked_at' => 'datetime'];
    }
}
