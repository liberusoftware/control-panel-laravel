<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;

final class Domain extends Model
{
    use HasUuids;

    protected $table = 'control_panel_domains';

    protected $fillable = ['team_id', 'account_id', 'hostname', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['status' => DomainStatus::class, 'metadata' => 'array'];
    }

    public function virtualHosts(): HasMany
    {
        return $this->hasMany(VirtualHost::class);
    }
}
