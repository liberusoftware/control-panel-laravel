<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Models\Workload;

final class DeleteWorkload
{
    public function execute(Workload $workload): void
    {
        if ($workload->status === 'running') {
            throw ValidationException::withMessages(['workload' => 'A running workload cannot be deleted.']);
        }

        $workload->delete();
    }
}
