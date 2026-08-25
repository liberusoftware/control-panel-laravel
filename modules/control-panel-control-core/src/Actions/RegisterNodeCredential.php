<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;

final class RegisterNodeCredential
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): NodeCredential
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $nodeId = (string) ($attributes['node_id'] ?? '');

        if ($name === '' || $nodeId === '' || (empty($attributes['secret']) && empty($attributes['public_key']))) {
            throw ValidationException::withMessages(['credential' => 'A node, name, and secret or public key are required.']);
        }

        $node = Node::query()->whereKey($nodeId)->where('team_id', $attributes['team_id'] ?? null)->first();
        if ($node === null) {
            throw ValidationException::withMessages(['node_id' => 'The node is not available in the current team.']);
        }

        return NodeCredential::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $node->team_id, 'node_id' => $node->getKey(),
            'name' => $name, 'type' => $attributes['type'] ?? 'ssh', 'username' => $attributes['username'] ?? null,
            'secret' => $attributes['secret'] ?? null, 'public_key' => $attributes['public_key'] ?? null,
            'status' => CredentialStatus::Active, 'expires_at' => $attributes['expires_at'] ?? null,
            'metadata' => $attributes['metadata'] ?? [],
        ]);
    }
}
