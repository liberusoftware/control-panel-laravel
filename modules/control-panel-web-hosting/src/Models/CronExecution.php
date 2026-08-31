<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CronExecution extends Model
{
    use HasUuids;

    protected $table = 'control_panel_cron_executions';

    protected $fillable = ['cron_job_id', 'started_at', 'finished_at', 'exit_code', 'output', 'error_output', 'duration'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime', 'exit_code' => 'integer', 'duration' => 'integer'];
    }

    public function cronJob(): BelongsTo
    {
        return $this->belongsTo(CronJob::class);
    }

    public function wasSuccessful(): bool
    {
        return $this->exit_code === 0;
    }

    public function failed(): bool
    {
        return ! $this->wasSuccessful();
    }

    public function getDurationInSecondsAttribute(): float
    {
        if ($this->started_at && $this->finished_at) {
            return abs((float) $this->finished_at->diffInSeconds($this->started_at));
        }

        return 0.0;
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('exit_code', 0);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('exit_code', '!=', 0);
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }
}
