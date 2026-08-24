<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Enums;

enum SnapshotStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Verified = 'verified';
    case Failed = 'failed';
    case Restored = 'restored';
}
