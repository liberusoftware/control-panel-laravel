<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\ApiCredential;

final class RevokeApiCredential
{
    public function execute(ApiCredential $credential): ApiCredential
    {
        if ($credential->status !== 'active') {
            throw ValidationException::withMessages(['credential' => 'Only active credentials can be revoked.']);
        }

        $credential->update(['status' => 'revoked']);

        return $credential->refresh();
    }
}
