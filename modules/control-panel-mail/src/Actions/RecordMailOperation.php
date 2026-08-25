<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailOperation;

final class RecordMailOperation
{
    public function execute(array $a): MailOperation
    {
        $op = (string) ($a['operation'] ?? '');
        if (! in_array($op, ['deliver', 'quarantine', 'spam-check', 'dkim-rotate'], true)) {
            throw ValidationException::withMessages(['operation' => 'Unsupported mail operation.']);
        }

return MailOperation::query()->create(['id' => (string) Str::uuid(), 'team_id' => $a['team_id'] ?? null, 'mail_account_id' => $a['mail_account_id'] ?? null, 'operation' => $op, 'status' => $a['status'] ?? 'queued', 'details' => $a['details'] ?? []]);
    }
}
