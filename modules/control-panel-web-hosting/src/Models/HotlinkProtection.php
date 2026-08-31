<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HotlinkProtection extends Model
{
    use HasUuids;

    protected $table = 'control_panel_hotlink_protections';

    protected $fillable = ['team_id', 'domain_id', 'enabled', 'allowed_domains', 'protected_extensions', 'redirect_url', 'allow_blank_referrer'];

    protected function casts(): array
    {
        return ['enabled' => 'bool', 'allowed_domains' => 'array', 'protected_extensions' => 'array', 'allow_blank_referrer' => 'bool'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
