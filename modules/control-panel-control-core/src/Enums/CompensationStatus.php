<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Enums;

enum CompensationStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
