<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;

final class CordonNode
{
    public function execute(KubernetesNode $node): KubernetesNode
    {
        return DB::transaction(function () use ($node): KubernetesNode {
            $locked = KubernetesNode::query()->whereKey($node->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->schedulable) {
                throw ValidationException::withMessages(['node' => 'The node is already cordoned.']);
            }
            $locked->forceFill(['schedulable' => false, 'status' => 'SchedulingDisabled'])->save();

            return $locked->refresh();
        });
    }
}
