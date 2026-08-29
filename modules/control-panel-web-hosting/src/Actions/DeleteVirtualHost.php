<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\VirtualHost;

final class DeleteVirtualHost
{
    public function execute(VirtualHost $virtualHost): void
    {
        $virtualHost->delete();
    }
}
