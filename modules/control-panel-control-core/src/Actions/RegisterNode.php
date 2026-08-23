<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\NodeStatus;
use Liberu\ControlPanel\ControlCore\Events\NodeRegistered;
use Liberu\ControlPanel\ControlCore\Models\Node;

final readonly class RegisterNode
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): Node
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $hostname = trim((string) ($attributes['hostname'] ?? ''));

        if ($name === '' || $hostname === '') {
            throw ValidationException::withMessages([
                'name' => 'A node name is required.',
                'hostname' => 'A node hostname is required.',
            ]);
        }

        return DB::transaction(function () use ($attributes, $name, $hostname): Node {
            $node = Node::query()->create([
                'id' => (string) Str::uuid(),
                'team_id' => $attributes['team_id'] ?? null,
                'name' => $name,
                'hostname' => $hostname,
                'platform' => $attributes['platform'] ?? null,
                'status' => NodeStatus::Pending,
                'credentials' => $attributes['credentials'] ?? null,
                'desired_state' => $attributes['desired_state'] ?? [],
                'observed_state' => [],
            ]);

            $this->events->dispatch(new NodeRegistered($node->getKey()));

            return $node;
        });
    }
}
