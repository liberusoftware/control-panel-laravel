<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\ControlPanel\Dns\Enums\ZoneStatus;

final class Zone extends Model
{
    use HasUuids;

    protected $table = 'control_panel_dns_zones';

    protected $fillable = ['team_id', 'domain', 'status', 'provider', 'dnssec_enabled', 'metadata'];

    protected function casts(): array
    {
        return ['status' => ZoneStatus::class, 'dnssec_enabled' => 'bool', 'metadata' => 'array'];
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }
}
