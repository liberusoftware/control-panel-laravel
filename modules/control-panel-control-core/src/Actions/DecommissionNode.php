<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\NodeStatus;
use Liberu\ControlPanel\ControlCore\Models\Node;

final class DecommissionNode
{
    public function execute(Node $node): Node
    {
        if ($node->status === NodeStatus::Decommissioned) {
            throw ValidationException::withMessages(['node' => 'The node is already decommissioned.']);
        }

        return DB::transaction(function () use ($node): Node {
            $node->forceFill(['status' => NodeStatus::Decommissioned])->save();

            return $node->refresh();
        });
    }
}
