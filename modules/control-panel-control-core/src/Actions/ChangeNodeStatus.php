<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\NodeStatus;
use Liberu\ControlPanel\ControlCore\Models\Node;

final class ChangeNodeStatus
{
    public function execute(Node $node, NodeStatus $status): Node
    {
        if ($node->status === NodeStatus::Decommissioned && $status !== NodeStatus::Decommissioned) {
            throw ValidationException::withMessages(['status' => 'A decommissioned node cannot be reactivated.']);
        }

        return DB::transaction(function () use ($node, $status): Node {
            $node->forceFill(['status' => $status])->save();

            return $node->refresh();
        });
    }
}
