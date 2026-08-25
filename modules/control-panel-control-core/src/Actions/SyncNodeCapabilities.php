<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Models\Node;

final class SyncNodeCapabilities
{
    /** @param array<int, array{name: string, version?: string|null, metadata?: array<string, mixed>}> $capabilities */
    public function execute(Node $node, array $capabilities): Node
    {
        $normalised = [];
        foreach ($capabilities as $capability) {
            $name = trim((string) ($capability['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages(['capabilities' => 'Every capability must have a name.']);
            }
            $normalised[$name] = [
                'id' => (string) Str::uuid(),
                'name' => $name,
                'version' => $capability['version'] ?? null,
                'metadata' => $capability['metadata'] ?? [],
            ];
        }

        $node->capabilities()->delete();
        foreach ($normalised as $capability) {
            $node->capabilities()->create($capability);
        }

        return $node->load('capabilities');
    }
}
