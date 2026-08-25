<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Models;

use Illuminate\Database\Eloquent\Model;

final class DnsValidation extends Model
{
    protected $table = 'control_panel_dns_validations';

    protected $fillable = ['id', 'team_id', 'zone_id', 'record_id', 'status', 'resolver', 'expected', 'observed', 'checked_at', 'details'];

    protected function casts(): array
    {
        return ['expected' => 'array', 'observed' => 'array', 'checked_at' => 'datetime', 'details' => 'array'];
    }
}
