<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Services;

use Carbon\CarbonImmutable;
use Cron\CronExpression;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DateTimeZoneInvalidTimeZoneException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class BackupScheduleCalculator
{
    public function next(string $cron, string $timezone, DateTimeInterface $after): CarbonImmutable
    {
        if (! CronExpression::isValidExpression($cron)) {
            throw ValidationException::withMessages(['cron' => 'A valid cron schedule is required.']);
        }
        try {
            $zone = new DateTimeZone($timezone);
        } catch (DateTimeZoneInvalidTimeZoneException) {
            throw ValidationException::withMessages(['timezone' => 'A valid timezone is required.']);
        }
        try {
            $next = (new CronExpression($cron))->getNextRunDate(DateTimeImmutable::createFromInterface($after)->setTimezone($zone), 0, false, $timezone);
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['cron' => 'The schedule must have a future run date.']);
        }

        return CarbonImmutable::instance($next)->utc();
    }
}
