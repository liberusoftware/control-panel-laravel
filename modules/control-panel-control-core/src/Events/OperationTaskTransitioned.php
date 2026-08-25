<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class OperationTaskTransitioned implements ShouldDispatchAfterCommit
{
    public function __construct(public string $taskId, public string $status) {}
}
