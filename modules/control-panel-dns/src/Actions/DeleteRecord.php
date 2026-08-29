<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Liberu\ControlPanel\Dns\Models\Record;

final class DeleteRecord
{
    public function execute(Record $record): void
    {
        $record->delete();
    }
}
