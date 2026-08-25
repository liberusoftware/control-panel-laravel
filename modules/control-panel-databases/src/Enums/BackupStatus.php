<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Enums;

enum BackupStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
