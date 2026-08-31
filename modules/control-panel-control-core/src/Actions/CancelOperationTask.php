<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final readonly class CancelOperationTask
{
    public function __construct(private TransitionOperationTask $transition) {}

    public function execute(OperationTask $task): OperationTask
    {
        return $this->transition->execute($task, TaskStatus::Cancelled, null, 'Cancelled by operator.');
    }
}
