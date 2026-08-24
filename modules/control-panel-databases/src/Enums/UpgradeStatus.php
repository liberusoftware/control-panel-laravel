<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Enums;

enum UpgradeStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
