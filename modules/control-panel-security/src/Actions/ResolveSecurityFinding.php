<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Models\SecurityFinding;

final class ResolveSecurityFinding
{
    public function execute(SecurityFinding $finding): SecurityFinding
    {
        if ($finding->status !== 'open') {
            throw ValidationException::withMessages(['finding' => 'Only open security findings can be resolved.']);
        }

        $finding->update(['status' => 'resolved']);

        return $finding->refresh();
    }
}
