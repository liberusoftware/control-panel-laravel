<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;

final class SuspendCluster
{
    public function execute(Cluster $cluster): Cluster
    {
        if ($cluster->status === 'archived') {
            throw ValidationException::withMessages(['cluster' => 'An archived cluster cannot be suspended.']);
        }
        if ($cluster->status === 'suspended') {
            throw ValidationException::withMessages(['cluster' => 'The cluster is already suspended.']);
        }

        return DB::transaction(function () use ($cluster): Cluster {
            $cluster->forceFill(['status' => 'suspended'])->save();

            return $cluster->refresh();
        });
    }
}
