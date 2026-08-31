<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\Redirect;

final class DeleteRedirect
{
    public function execute(Redirect $redirect): void
    {
        $redirect->delete();
    }
}
