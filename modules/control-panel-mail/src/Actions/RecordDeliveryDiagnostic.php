<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\DeliveryDiagnostic;
use Liberu\ControlPanel\Mail\Models\MailAccount;

final class RecordDeliveryDiagnostic
{
    public function execute(array $a): DeliveryDiagnostic
    {
        $teamId = trim((string) ($a['team_id'] ?? ''));
        $accountId = $a['mail_account_id'] ?? null;
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A tenant is required.']);
        }
        if ($accountId !== null && ! MailAccount::query()->whereKey($accountId)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return DeliveryDiagnostic::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'mail_account_id' => $accountId, 'message_id' => $a['message_id'] ?? null, 'recipient' => $a['recipient'], 'status' => $a['status'] ?? 'pending', 'response' => $a['response'] ?? null, 'checked_at' => now()]);
    }
}
