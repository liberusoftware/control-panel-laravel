<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCore\Models\OperationLock;

final class AcquireOperationLock
{
    public function execute(string|int $teamId, string $nodeId, string $operationKey, string $owner, int $ttlSeconds = 300): OperationLock
    {
        return DB::transaction(function () use ($teamId, $nodeId, $operationKey, $owner, $ttlSeconds): OperationLock {
            if (! Node::query()->whereKey($nodeId)->where('team_id', $teamId)->exists()) {
                throw ValidationException::withMessages(['node_id' => 'The node is not available in the current team.']);
            }

            $existing = OperationLock::query()->where(['node_id' => $nodeId, 'operation_key' => $operationKey])->lockForUpdate()->first();
            if ($existing !== null && $existing->expires_at?->isFuture()) {
                throw ValidationException::withMessages(['operation' => 'The node is already locked for this operation.']);
            }
            $lock = $existing ?? new OperationLock(['node_id' => $nodeId, 'operation_key' => $operationKey]);
            $lock->fill(['team_id' => $teamId, 'owner' => $owner, 'expires_at' => Carbon::now()->addSeconds(max($ttlSeconds, 1))]);
            $lock->save();

            return $lock->refresh();
        });
    }
}
