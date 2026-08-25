<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Models\OperationLock;

final class ReleaseOperationLock
{
    public function execute(OperationLock $lock, string $owner): void
    {
        if (! hash_equals($lock->owner, $owner)) {
            throw ValidationException::withMessages(['owner' => 'Only the lock owner can release this operation lock.']);
        }

        $lock->delete();
    }
}
