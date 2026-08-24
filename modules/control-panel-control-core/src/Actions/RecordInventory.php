<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Liberu\ControlPanel\ControlCore\Events\InventoryRecorded;
use Liberu\ControlPanel\ControlCore\Models\InventoryRecord;

final readonly class RecordInventory
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): InventoryRecord
    {
        $record = InventoryRecord::query()->updateOrCreate(
            ['node_id' => $attributes['node_id'], 'kind' => $attributes['kind'], 'record_key' => $attributes['record_key']],
            ['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'value' => $attributes['value'] ?? [], 'observed_at' => $attributes['observed_at'] ?? now()],
        );
        $this->events->dispatch(new InventoryRecorded($record->getKey()));

        return $record;
    }
}
