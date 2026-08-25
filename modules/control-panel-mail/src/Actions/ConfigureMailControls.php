<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailControl;

final class ConfigureMailControls
{
    public function execute(array $a): MailControl
    {
        $threshold = (int) ($a['spam_threshold'] ?? 5);
        if ($threshold < 1 || $threshold > 30) {
            throw ValidationException::withMessages(['spam_threshold' => 'Spam threshold must be between 1 and 30.']);
        }

        return MailControl::query()->updateOrCreate(['team_id' => $a['team_id'] ?? null, 'mail_account_id' => $a['mail_account_id']], array_merge($a, ['id' => $a['id'] ?? (string) Str::uuid(), 'spam_threshold' => $threshold, 'spam_action' => $a['spam_action'] ?? 'quarantine']));
    }
}
