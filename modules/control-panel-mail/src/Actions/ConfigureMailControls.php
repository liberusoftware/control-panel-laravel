<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Carbon;
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

        $existing = MailControl::query()->where('team_id', $teamId)->where('mail_account_id', $a['mail_account_id'])->first();

        $attributes = validator($a, [
            'spam_filter_enabled' => ['sometimes', 'boolean'],
            'spam_action' => ['sometimes', 'in:tag,quarantine,reject'],
            'virus_scan_enabled' => ['sometimes', 'boolean'],
            'autoresponder_enabled' => ['sometimes', 'boolean'],
            'autoresponder_subject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'autoresponder_message' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'autoresponder_start_at' => ['sometimes', 'nullable', 'date'],
            'autoresponder_end_at' => ['sometimes', 'nullable', 'date'],
            'keep_copy_on_server' => ['sometimes', 'boolean'],
        ])->validate();

        $startAt = array_key_exists('autoresponder_start_at', $attributes)
            ? ($attributes['autoresponder_start_at'] !== null ? Carbon::parse($attributes['autoresponder_start_at']) : null)
            : $existing?->autoresponder_start_at;
        $endAt = array_key_exists('autoresponder_end_at', $attributes)
            ? ($attributes['autoresponder_end_at'] !== null ? Carbon::parse($attributes['autoresponder_end_at']) : null)
            : $existing?->autoresponder_end_at;
        if ($startAt !== null && $endAt !== null && $endAt->lt($startAt)) {
            throw ValidationException::withMessages(['autoresponder_end_at' => 'The autoresponder end must be after its start.']);
        }

        return MailControl::query()->updateOrCreate(['team_id' => $teamId, 'mail_account_id' => $a['mail_account_id']], array_merge($attributes, ['id' => $existing?->getKey() ?? $a['id'] ?? (string) Str::uuid(), 'team_id' => $teamId, 'spam_threshold' => $threshold, 'spam_action' => $a['spam_action'] ?? $existing?->spam_action ?? 'quarantine', 'autoresponder_start_at' => $startAt, 'autoresponder_end_at' => $endAt]));
    }
}
