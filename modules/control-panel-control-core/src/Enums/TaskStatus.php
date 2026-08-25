<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
