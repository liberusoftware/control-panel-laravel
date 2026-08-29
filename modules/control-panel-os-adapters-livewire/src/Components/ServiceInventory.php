<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\OsAdapters\Actions\UpdateOsService;
use Liberu\ControlPanel\OsAdapters\Models\OsService;
use Livewire\Component;
use Livewire\WithPagination;

final class ServiceInventory extends Component
{
    use WithPagination;

    /** @var array<string, array<string, mixed>> */
    public array $edits = [];

    /** @param array<string, mixed>|null $attributes */
    public function update(string $serviceId, ?array $attributes, UpdateOsService $update): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $service = OsService::query()->whereKey($serviceId)->where('team_id', $teamId)->firstOrFail();
        $attributes ??= $this->edits[$serviceId] ?? [];
        $attributes = validator($attributes, ['name' => ['required', 'string', 'max:160'], 'version' => ['nullable', 'string', 'max:80'], 'status' => ['required', 'string', 'max:40'], 'enabled' => ['sometimes', 'boolean']])->validate();
        $update->execute($service, $attributes);
        unset($this->edits[$serviceId]);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-os-adapters-livewire::components.service-inventory', ['services' => OsService::query()->where('team_id', $teamId)->latest()->paginate(25)]);
    }
}
