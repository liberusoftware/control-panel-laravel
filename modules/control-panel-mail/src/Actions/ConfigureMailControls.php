<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\Mail\Models\MailControl;

final class ConfigureMailControls
{
    public function execute(array $a): MailControl
    {
        $threshold = (int) ($a['spam_threshold'] ?? 5);
        if ($threshold < 1 || $threshold > 30) {
            throw ValidationException::withMessages(['spam_threshold' => 'Spam threshold must be between 1 and 30.']);
        }
        $teamId = trim((string) ($a['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A tenant is required.']);
        }
        if (! MailAccount::query()->whereKey($a['mail_account_id'] ?? null)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return MailControl::query()->updateOrCreate(['team_id' => $teamId, 'mail_account_id' => $a['mail_account_id']], array_merge($a, ['id' => $a['id'] ?? (string) Str::uuid(), 'team_id' => $teamId, 'spam_threshold' => $threshold, 'spam_action' => $a['spam_action'] ?? 'quarantine']));
    }
}
