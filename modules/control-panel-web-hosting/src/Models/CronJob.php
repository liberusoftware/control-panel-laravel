<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CronJob extends Model
{
    use HasUuids;

    public const SCHEDULE_EVERY_MINUTE = '* * * * *';

    public const SCHEDULE_HOURLY = '0 * * * *';

    public const SCHEDULE_DAILY = '0 0 * * *';

    public const SCHEDULE_WEEKLY = '0 0 * * 0';

    public const SCHEDULE_MONTHLY = '0 0 1 * *';

    protected $table = 'control_panel_cron_jobs';

    protected $fillable = ['team_id', 'domain_id', 'name', 'command', 'schedule', 'active', 'last_run_at', 'next_run_at', 'output', 'error_output'];

    protected function casts(): array
    {
        return ['active' => 'bool', 'last_run_at' => 'datetime', 'next_run_at' => 'datetime'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(CronExecution::class);
    }

    public static function getCommonSchedules(): array
    {
        return [
            self::SCHEDULE_EVERY_MINUTE => 'Every minute',
            '*/5 * * * *' => 'Every 5 minutes',
            '*/15 * * * *' => 'Every 15 minutes',
            '*/30 * * * *' => 'Every 30 minutes',
            self::SCHEDULE_HOURLY => 'Every hour',
            '0 */6 * * *' => 'Every 6 hours',
            '0 */12 * * *' => 'Every 12 hours',
            self::SCHEDULE_DAILY => 'Daily at midnight',
            '0 6 * * *' => 'Daily at 6 AM',
            '0 12 * * *' => 'Daily at noon',
            '0 18 * * *' => 'Daily at 6 PM',
            self::SCHEDULE_WEEKLY => 'Weekly on Sunday',
            self::SCHEDULE_MONTHLY => 'Monthly on 1st',
        ];
    }

    public function getHumanScheduleAttribute(): string
    {
        return self::getCommonSchedules()[$this->schedule] ?? $this->schedule;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->active()->where(fn (Builder $due): Builder => $due->whereNull('next_run_at')->orWhere('next_run_at', '<=', now()));
    }
}
