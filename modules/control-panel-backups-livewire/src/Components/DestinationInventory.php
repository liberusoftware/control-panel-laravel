<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Backups\Actions\DeleteDestination;
use Liberu\ControlPanel\Backups\Actions\UpdateDestination;
use Liberu\ControlPanel\Backups\Models\BackupDestination;
use Livewire\Component;

final class DestinationInventory extends Component
{
    public int $perPage = 25;

    /** @var array<string, array<string, mixed>> */
    public array $destinationEdits = [];

    /** @param array<string, mixed>|null $attributes */
    public function updateDestination(string $destinationId, ?array $attributes, UpdateDestination $update): void
    {
        $destination = BackupDestination::query()->whereKey($destinationId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->destinationEdits[$destinationId] ?? [];
        validator($attributes, ['name' => ['required', 'string', 'max:120'], 'driver' => ['required', 'in:local,s3,sftp,ftp'], 'retention_days' => ['required', 'integer', 'min:1'], 'active' => ['sometimes', 'boolean']])->validate();
        $update->execute($destination, $attributes);
        unset($this->destinationEdits[$destinationId]);
    }

    public function deleteDestination(string $destinationId, DeleteDestination $delete): void
    {
        $destination = BackupDestination::query()->whereKey($destinationId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($destination);
        unset($this->destinationEdits[$destinationId]);
    }

    public function render(): View
    {
        return view('control-panel-backups-livewire::components.destination-inventory', ['destinations' => BackupDestination::query()->where('team_id', $this->teamId())->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
