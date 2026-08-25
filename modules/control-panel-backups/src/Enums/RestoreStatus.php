<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Enums;

enum RestoreStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
