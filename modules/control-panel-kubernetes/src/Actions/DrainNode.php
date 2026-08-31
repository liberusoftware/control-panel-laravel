<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;

final class DrainNode
{
    /** @param array<string, mixed> $options */
    public function execute(KubernetesNode $node, array $options = []): KubernetesNode
    {
        validator($options, [
            'force' => ['sometimes', 'boolean'],
            'grace_period' => ['sometimes', 'integer', 'min:0'],
            'timeout' => ['sometimes', 'string', 'max:32'],
        ])->validate();

        return DB::transaction(function () use ($node): KubernetesNode {
            $locked = KubernetesNode::query()->whereKey($node->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->schedulable) {
                throw ValidationException::withMessages(['node' => 'The node is already unschedulable.']);
            }
            $locked->forceFill(['schedulable' => false, 'status' => 'SchedulingDisabled'])->save();

            return $locked->refresh();
        });
    }
}
