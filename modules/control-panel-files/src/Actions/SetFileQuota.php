<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Models\FileQuota;

final class SetFileQuota
{
    public function execute(array $attributes): FileQuota
    {
        $teamId = $attributes['team_id'] ?? null;
        $ownerId = $attributes['owner_id'] ?? null;
        $limitBytes = (int) ($attributes['limit_bytes'] ?? 0);
        $usedBytes = (int) ($attributes['used_bytes'] ?? 0);
        $filesCount = (int) ($attributes['files_count'] ?? 0);

        if (! is_string($teamId) || $teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }
        if ($limitBytes < 0 || $usedBytes < 0 || $filesCount < 0) {
            throw ValidationException::withMessages(['quota' => 'Quota values cannot be negative.']);
        }
        if ($limitBytes > 0 && $usedBytes > $limitBytes) {
            throw ValidationException::withMessages(['limit_bytes' => 'The quota limit cannot be below current usage.']);
        }

        return DB::transaction(function () use ($teamId, $ownerId, $limitBytes, $usedBytes, $filesCount): FileQuota {
            $quota = FileQuota::query()->where('team_id', $teamId)->where('owner_id', $ownerId)->lockForUpdate()->first();
            $values = ['limit_bytes' => $limitBytes, 'used_bytes' => $usedBytes, 'files_count' => $filesCount];

            if ($quota === null) {
                return FileQuota::query()->create(array_merge(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'owner_id' => $ownerId], $values));
            }

            $quota->update($values);

            return $quota->refresh();
        });
    }
}
