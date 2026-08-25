<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Enums;

enum AutomationStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Failed = 'failed';
    case Completed = 'completed';
}
