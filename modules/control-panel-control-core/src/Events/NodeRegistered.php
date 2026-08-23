<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class NodeRegistered implements ShouldDispatchAfterCommit
{
    public function __construct(public string $nodeId) {}
}
