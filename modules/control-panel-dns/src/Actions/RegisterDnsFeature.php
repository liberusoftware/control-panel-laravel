<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Models\DnsProvider;
use Liberu\ControlPanel\Dns\Models\DnssecKey;
use Liberu\ControlPanel\Dns\Models\DnsTemplate;
use Liberu\ControlPanel\Dns\Models\DnsValidation;
use Liberu\ControlPanel\Dns\Models\PropagationCheck;

final class RegisterDnsFeature
{
    public function execute(array $a): Model
    {
        $kind = (string) ($a['kind'] ?? '');
        $map = ['template' => DnsTemplate::class, 'dnssec' => DnssecKey::class, 'provider' => DnsProvider::class, 'validation' => DnsValidation::class, 'propagation' => PropagationCheck::class];
        if (! isset($map[$kind])) {
            throw ValidationException::withMessages(['kind' => 'Unsupported DNS feature.']);
        } $a['id'] = $a['id'] ?? (string) Str::uuid();
        $a['team_id'] = $a['team_id'] ?? null;
        unset($a['kind']);

        return $map[$kind]::query()->create($a);
    }
}
