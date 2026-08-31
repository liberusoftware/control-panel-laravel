<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupDestination;

final class RecordDestinationHealth
{
    /** @param array<string, mixed> $details */
    public function execute(BackupDestination $destination, bool $healthy, ?int $latencyMs = null, ?string $message = null, array $details = []): BackupDestination
    {
        if ($latencyMs !== null && $latencyMs < 0) {
            throw ValidationException::withMessages(['latency_ms' => 'Latency must be zero or greater.']);
        }

        if ($message !== null && mb_strlen($message) > 1000) {
            throw ValidationException::withMessages(['message' => 'The health message may not exceed 1000 characters.']);
        }

        return DB::transaction(function () use ($destination, $healthy, $latencyMs, $message, $details): BackupDestination {
            $destination->forceFill([
                'last_checked_at' => now(),
                'health' => ['healthy' => $healthy, 'latency_ms' => $latencyMs, 'message' => $message, 'details' => $details],
            ])->save();

            return $destination->refresh();
        });
    }
}
