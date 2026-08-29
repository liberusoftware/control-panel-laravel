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
        if ($workload->status === 'running') {
            throw ValidationException::withMessages(['workload' => 'The workload is already running.']);
        }

        return DB::transaction(function () use ($workload): Workload {
            $workload->forceFill(['status' => 'running'])->save();

            return $workload->refresh();
        });
    }
}
