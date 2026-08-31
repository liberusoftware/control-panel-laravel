<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\ResourceUsage;

final class RecordResourceUsage
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): ResourceUsage
    {
        $teamId = $attributes['team_id'] ?? $domain->team_id;
        if ((string) $teamId !== (string) $domain->team_id) {
            throw ValidationException::withMessages(['domain' => 'The domain does not belong to this team.']);
        }

        $data = validator($attributes, [
            'month' => ['sometimes', 'integer', 'between:1,12'],
            'year' => ['sometimes', 'integer', 'between:2000,2200'],
            'disk_usage_mb' => ['sometimes', 'integer', 'min:0'],
            'bandwidth_usage_mb' => ['sometimes', 'integer', 'min:0'],
        ])->validate();

        $month = (int) ($data['month'] ?? now()->month);
        $year = (int) ($data['year'] ?? now()->year);

        return ResourceUsage::query()->updateOrCreate(
            ['team_id' => $domain->team_id, 'domain_id' => $domain->getKey(), 'month' => $month, 'year' => $year],
            [
                'disk_usage_mb' => (int) ($data['disk_usage_mb'] ?? 0),
                'bandwidth_usage_mb' => (int) ($data['bandwidth_usage_mb'] ?? 0),
            ],
        );
    }
}
