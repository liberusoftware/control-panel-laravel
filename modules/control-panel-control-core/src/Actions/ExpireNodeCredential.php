<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;

final class ExpireNodeCredential
{
    public function execute(NodeCredential $credential): NodeCredential
    {
        if ($credential->status !== CredentialStatus::Active) {
            throw ValidationException::withMessages(['credential' => 'Only active credentials can be expired.']);
        }

        if ($credential->expires_at === null || Carbon::parse($credential->expires_at)->isFuture()) {
            throw ValidationException::withMessages(['expires_at' => 'The credential expiration time must be in the past.']);
        }

        return DB::transaction(function () use ($credential): NodeCredential {
            $credential->forceFill(['status' => CredentialStatus::Expired])->save();

            return $credential->refresh();
        });
    }
}
