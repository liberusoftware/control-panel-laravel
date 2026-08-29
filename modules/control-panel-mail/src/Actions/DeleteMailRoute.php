<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Liberu\ControlPanel\Mail\Models\MailRoute;

final class DeleteMailRoute
{
    public function execute(MailRoute $route): void
    {
        $route->delete();
    }
}
