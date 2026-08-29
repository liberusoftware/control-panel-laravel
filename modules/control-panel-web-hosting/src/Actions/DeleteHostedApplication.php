<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\HostedApplication;

final class DeleteHostedApplication
{
    public function execute(HostedApplication $application): void
    {
        $application->delete();
    }
}
