<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Models\Workload;

final class StartWorkload
{
    public function execute(Workload $workload): Workload
    {
        return DB::transaction(function () use ($workload): Workload {
            $locked = Workload::query()->whereKey($workload->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status === 'running') {
                throw ValidationException::withMessages(['workload' => 'The workload is already running.']);
            }
            $locked->forceFill(['status' => 'running'])->save();

            return $locked->refresh();
        });
    }
}
