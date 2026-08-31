<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Models\Workload;

final class DeleteWorkload
{
    public function execute(Workload $workload): void
    {
        DB::transaction(function () use ($workload): void {
            $locked = Workload::query()->whereKey($workload->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status === 'running') {
                throw ValidationException::withMessages(['workload' => 'A running workload cannot be deleted.']);
            }
            $locked->delete();
        });
    }
}
