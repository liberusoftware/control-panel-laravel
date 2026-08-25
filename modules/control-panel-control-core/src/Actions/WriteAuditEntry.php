<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Support\Str;
use Liberu\ControlPanel\ControlCore\Models\AuditEntry;

final class WriteAuditEntry
{
    /** @param array<string, mixed> $context */
    public function execute(string $event, ?string $teamId, ?string $actorId, string $subjectType, string $subjectId, array $context = []): AuditEntry
    {
        return AuditEntry::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $teamId, 'actor_id' => $actorId,
            'event' => $event, 'subject_type' => $subjectType, 'subject_id' => $subjectId,
            'context' => $context,
        ]);
    }
}
