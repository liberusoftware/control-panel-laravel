<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Models\SecurityFinding;

final class UpdateSecurityFinding
{
    /** @param array<string, mixed> $attributes */
    public function execute(SecurityFinding $finding, array $attributes): SecurityFinding
    {
        if ($finding->status !== 'open') {
            throw ValidationException::withMessages(['finding' => 'Only open security findings can be updated.']);
        }

        $subjectType = trim((string) ($attributes['subject_type'] ?? $finding->subject_type));
        $subjectId = trim((string) ($attributes['subject_id'] ?? $finding->subject_id));
        $code = trim((string) ($attributes['code'] ?? $finding->code));
        $summary = trim((string) ($attributes['summary'] ?? $finding->summary));
        $severity = (string) ($attributes['severity'] ?? $finding->severity);
        if ($subjectType === '' || $subjectId === '' || $code === '' || $summary === '' || ! in_array($severity, ['critical', 'high', 'medium', 'low', 'info'], true)) {
            throw ValidationException::withMessages(['finding' => 'A finding subject, code, severity, and summary are required.']);
        }

        $finding->forceFill(['subject_type' => $subjectType, 'subject_id' => $subjectId, 'code' => $code, 'severity' => $severity, 'summary' => $summary, 'evidence' => $attributes['evidence'] ?? $finding->evidence])->save();

        return $finding->refresh();
    }
}
