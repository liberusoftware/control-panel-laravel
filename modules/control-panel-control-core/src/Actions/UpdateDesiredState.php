<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Liberu\ControlPanel\ControlCore\Events\DesiredStateUpdated;
use Liberu\ControlPanel\ControlCore\Models\Node;

final readonly class UpdateDesiredState
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $state */
    public function execute(Node $node, array $state): Node
    {
        return DB::transaction(function () use ($node, $state): Node {
            $node->forceFill(['desired_state' => $state])->save();
            $this->events->dispatch(new DesiredStateUpdated($node->getKey()));

            return $node->refresh();
        });
    }
}
