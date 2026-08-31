<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResourceUsage extends Model
{
    use HasUuids;

    protected $table = 'control_panel_resource_usage';

    protected $fillable = ['id', 'team_id', 'domain_id', 'month', 'year', 'disk_usage_mb', 'bandwidth_usage_mb'];

    protected function casts(): array
    {
        return ['month' => 'integer', 'year' => 'integer', 'disk_usage_mb' => 'integer', 'bandwidth_usage_mb' => 'integer'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function scopeForDomain(Builder $query, string $domainId): Builder
    {
        return $query->where('domain_id', $domainId);
    }

    public function scopeForMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->where('month', $month)->where('year', $year);
    }
}
