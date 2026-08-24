<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Enums;

enum FileStatus: string
{
    case PendingScan = 'pending_scan';
    case Available = 'available';
    case Quarantined = 'quarantined';
    case Retained = 'retained';
    case Deleted = 'deleted';
}
