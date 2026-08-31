<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Actions;

use Illuminate\Support\Facades\Validator;
use Liberu\ControlPanel\OsAdapters\Models\FirewallRule;

final class UpdateFirewallRule
{
    /** @param array<string, mixed> $attributes */
    public function execute(FirewallRule $rule, array $attributes): FirewallRule
    {
        Validator::make($attributes, [
            'direction' => ['sometimes', 'in:inbound,outbound'],
            'action' => ['sometimes', 'in:allow,deny,reject'],
            'protocol' => ['sometimes', 'nullable', 'string', 'max:20'],
            'port' => ['sometimes', 'nullable', 'integer', 'between:1,65535'],
            'source' => ['sometimes', 'nullable', 'string', 'max:64'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ])->validate();
        if (array_key_exists('source', $attributes)) {
            app(CreateFirewallRule::class)->validateSource($attributes['source']);
        }

        $rule->fill($attributes)->save();

        return $rule->refresh();
    }
}
