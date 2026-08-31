<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Subdomain extends Model
{
    use HasUuids;

    public static function getRedirectTypes(): array
    {
        return [
            301 => 'Permanent (301)',
            302 => 'Temporary (302)',
        ];
    }

    protected $table = 'control_panel_subdomains';

    protected $fillable = ['id', 'domain_id', 'subdomain', 'document_root', 'php_version', 'active', 'redirect_url', 'redirect_type'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'redirect_type' => 'integer'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->subdomain.'.'.$this->domain->hostname;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
