<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Models\DnsProvider;
use Liberu\ControlPanel\Dns\Models\DnssecKey;
use Liberu\ControlPanel\Dns\Models\DnsTemplate;
use Liberu\ControlPanel\Dns\Models\DnsValidation;
use Liberu\ControlPanel\Dns\Models\PropagationCheck;
use Liberu\ControlPanel\Dns\Models\Record;
use Liberu\ControlPanel\Dns\Models\Zone;

final class RegisterDnsFeature
{
    public function execute(array $a): Model
    {
        $kind = (string) ($a['kind'] ?? '');
        $map = ['template' => DnsTemplate::class, 'dnssec' => DnssecKey::class, 'provider' => DnsProvider::class, 'validation' => DnsValidation::class, 'propagation' => PropagationCheck::class];
        if (! isset($map[$kind])) {
            throw ValidationException::withMessages(['kind' => 'Unsupported DNS feature.']);
        }

        $teamId = trim((string) ($a['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A tenant is required.']);
        }

        $rules = match ($kind) {
            'template' => [
                'name' => ['required', 'string', 'max:160'], 'records' => ['nullable', 'array', 'max:100'],
                'active' => ['sometimes', 'boolean'],
            ],
            'dnssec' => [
                'zone_id' => ['nullable', 'uuid'], 'key_tag' => ['nullable', 'integer', 'min:0', 'max:65535'],
                'algorithm' => ['nullable', 'integer', 'min:1', 'max:255'], 'digest_type' => ['nullable', 'integer', 'min:1', 'max:255'],
                'digest' => ['nullable', 'string', 'max:4096'], 'public_key' => ['nullable', 'string', 'max:10000'],
                'private_key' => ['nullable', 'string', 'max:16384'], 'active' => ['sometimes', 'boolean'],
                'rotated_at' => ['nullable', 'date'],
            ],
            'provider' => [
                'name' => ['required', 'string', 'max:160'], 'driver' => ['required', 'string', 'max:100'],
                'endpoint' => ['nullable', 'url', 'max:2048'], 'credentials' => ['nullable', 'array'],
                'settings' => ['nullable', 'array'], 'active' => ['sometimes', 'boolean'],
            ],
            'validation' => [
                'zone_id' => ['nullable', 'uuid'], 'record_id' => ['nullable', 'uuid'],
                'status' => ['nullable', 'in:pending,passed,failed'], 'resolver' => ['nullable', 'string', 'max:255'],
                'expected' => ['nullable', 'array'], 'observed' => ['nullable', 'array'],
                'checked_at' => ['nullable', 'date'], 'details' => ['nullable', 'array'],
            ],
            'propagation' => [
                'zone_id' => ['nullable', 'uuid'], 'record_id' => ['nullable', 'uuid'],
                'status' => ['nullable', 'in:pending,passed,failed'], 'nameservers' => ['nullable', 'array', 'max:100'],
                'results' => ['nullable', 'array'], 'checked_at' => ['nullable', 'date'],
            ],
        };

        $data = Validator::make($a, $rules)->validate();
        $this->assertRelatedResourcesBelongToTeam($data, $teamId);
        $data['id'] = $data['id'] ?? (string) Str::uuid();
        $data['team_id'] = $teamId;

        return $map[$kind]::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    private function assertRelatedResourcesBelongToTeam(array $data, string $teamId): void
    {
        if (! empty($data['zone_id'])) {
            abort_unless(Zone::query()->whereKey($data['zone_id'])->where('team_id', $teamId)->exists(), 404);
        }

        if (! empty($data['record_id'])) {
            abort_unless(Record::query()->whereKey($data['record_id'])->whereHas('zone', fn ($query) => $query->where('team_id', $teamId))->exists(), 404);
        }
    }
}
