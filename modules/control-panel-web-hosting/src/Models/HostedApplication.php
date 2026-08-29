<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class HostedApplication extends Model
{
    use HasUuids;

    protected $table = 'control_panel_hosted_applications';

    protected $fillable = ['team_id', 'domain_id', 'name', 'type', 'version', 'document_root', 'status', 'config'];

    protected $hidden = ['config'];

    protected function casts(): array
    {
        return ['config' => 'encrypted:array'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(ApplicationMetric::class, 'application_id');
    }

    public function healthStatus(): string
    {
        if ($this->status !== 'installed') {
            return 'inactive';
        }

        $uptime = $this->performanceMetrics()->where('checked_at', '>=', now()->subDays(30));
        $total = (clone $uptime)->count();
        if ($total === 0) {
            return 'unknown';
        }

        $percentage = ((clone $uptime)->where('healthy', true)->count() / $total) * 100;

        return $percentage >= 99.9 ? 'excellent' : ($percentage >= 99 ? 'good' : ($percentage >= 95 ? 'fair' : 'poor'));
    }

    public function isInstalled(): bool
    {
        return $this->status === 'installed';
    }

    public function isInstalling(): bool
    {
        return $this->status === 'installing';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isUpdating(): bool
    {
        return $this->status === 'updating';
    }

    public function getFullPathAttribute(): string
    {
        return rtrim($this->document_root, '/');
    }
}
