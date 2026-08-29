<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Liberu\ControlPanel\Mail\Models\MailAlias;

final class DeleteMailAlias
{
    public function execute(MailAlias $alias): void
    {
        $alias->delete();
    }
}
