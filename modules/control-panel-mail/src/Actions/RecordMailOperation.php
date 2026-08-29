<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\Mail\Models\MailOperation;

final class RecordMailOperation
{
    public function execute(array $a): MailOperation
    {
        $op = (string) ($a['operation'] ?? '');
        if (! in_array($op, ['deliver', 'quarantine', 'spam-check', 'dkim-rotate'], true)) {
            throw ValidationException::withMessages(['operation' => 'Unsupported mail operation.']);
        }
        $teamId = trim((string) ($a['team_id'] ?? ''));
        $accountId = $a['mail_account_id'] ?? null;
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A tenant is required.']);
        }
        if ($accountId !== null && ! MailAccount::query()->whereKey($accountId)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return MailOperation::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'mail_account_id' => $accountId, 'operation' => $op, 'status' => $a['status'] ?? 'queued', 'details' => $a['details'] ?? []]);
    }
}
