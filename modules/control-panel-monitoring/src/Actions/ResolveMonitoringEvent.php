<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Monitoring\Models\MonitoringEvent;

final class ResolveMonitoringEvent
{
    public function execute(MonitoringEvent $event): MonitoringEvent
    {
        if ($event->kind !== 'incident' || $event->status !== 'open') {
            throw ValidationException::withMessages(['event' => 'Only open incident events can be resolved.']);
        }

        $event->update(['status' => 'resolved', 'ends_at' => $event->ends_at ?? now()]);

        return $event->refresh();
    }
}
