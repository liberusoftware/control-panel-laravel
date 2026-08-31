<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupDestination;

final class DeleteDestination
{
    public function execute(BackupDestination $destination): void
    {
        if ($destination->default) {
            throw ValidationException::withMessages(['destination' => 'The default backup destination cannot be deleted.']);
        }

        $destination->delete();
    }
}
