<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Models\Workload;

final class StopWorkload
{
    public function execute(Workload $workload): Workload
    {
        if ($workload->status === 'stopped') {
            throw ValidationException::withMessages(['workload' => 'The workload is already stopped.']);
        }

        return DB::transaction(function () use ($workload): Workload {
            $workload->forceFill(['status' => 'stopped'])->save();

            return $workload->refresh();
        });
    }
}
