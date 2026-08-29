<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Contracts\DnsResolver;
use Liberu\ControlPanel\Dns\Models\DnsValidation;
use Liberu\ControlPanel\Dns\Models\Record;
use Liberu\ControlPanel\Dns\Models\Zone;

final class CheckDnsResolution
{
    public function __construct(private readonly DnsResolver $resolver) {}

    /** @return array{success: bool, validation: DnsValidation} */
    public function execute(array $attributes): array
    {
        $teamId = trim((string) ($attributes['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A tenant is required.']);
        }

        $zone = Zone::query()->whereKey($attributes['zone_id'] ?? null)->where('team_id', $teamId)->firstOrFail();
        $record = null;
        if (! empty($attributes['record_id'])) {
            $record = Record::query()->whereKey($attributes['record_id'])->where('zone_id', $zone->getKey())->firstOrFail();
        }

        $recordType = strtoupper((string) ($attributes['record_type'] ?? $record?->type ?? 'A'));
        if (! in_array($recordType, ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'PTR', 'SRV', 'CAA'], true)) {
            throw ValidationException::withMessages(['record_type' => 'Unsupported DNS record type.']);
        }

        $hostname = $this->hostname($zone->domain, $record);
        $observed = $this->resolver->records($hostname, $recordType);
        $expected = $record === null ? [] : ['type' => $recordType, 'content' => $record->content];
        $success = $record === null ? $observed !== [] : $this->containsContent($observed, $recordType, (string) $record->content);
        $validation = DnsValidation::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $teamId, 'zone_id' => $zone->getKey(), 'record_id' => $record?->getKey(),
            'status' => $success ? 'passed' : 'failed', 'resolver' => $attributes['resolver'] ?? gethostname(), 'expected' => $expected,
            'observed' => $observed, 'checked_at' => now(), 'details' => ['hostname' => $hostname, 'record_type' => $recordType],
        ]);

        return ['success' => $success, 'validation' => $validation];
    }

    private function hostname(string $domain, ?Record $record): string
    {
        if ($record === null || $record->name === null || $record->name === '' || $record->name === '@') {
            return $domain;
        }

        return rtrim((string) $record->name, '.').'.'.$domain;
    }

    /** @param array<int, array<string, mixed>> $observed */
    private function containsContent(array $observed, string $type, string $content): bool
    {
        return collect($observed)->contains(function (array $item) use ($type, $content): bool {
            $value = $type === 'MX' ? ($item['target'] ?? '') : ($item['ip'] ?? $item['host'] ?? $item['txt'] ?? $item['target'] ?? $item['value'] ?? '');

            return strcasecmp(trim((string) $value, '.'), trim($content, '.')) === 0;
        });
    }
}
