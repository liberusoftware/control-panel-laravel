<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Liberu\ControlPanel\Databases\Models\Database;

final class DeleteDatabase
{
    public function execute(Database $database): void
    {
        $database->delete();
    }
}
