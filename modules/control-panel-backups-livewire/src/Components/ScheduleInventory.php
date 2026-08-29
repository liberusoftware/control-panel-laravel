<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Backups\Actions\DeleteSchedule;
use Liberu\ControlPanel\Backups\Actions\UpdateSchedule;
use Liberu\ControlPanel\Backups\Models\BackupSchedule;
use Livewire\Component;

final class ScheduleInventory extends Component
{
    public int $perPage = 25;

    /** @var array<string, array<string, mixed>> */
    public array $scheduleEdits = [];

    /** @param array<string, mixed>|null $attributes */
    public function updateSchedule(string $scheduleId, ?array $attributes, UpdateSchedule $update): void
    {
        $schedule = BackupSchedule::query()->whereKey($scheduleId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->scheduleEdits[$scheduleId] ?? [];
        validator($attributes, ['cron' => ['required', 'string', 'max:120'], 'timezone' => ['required', 'timezone'], 'active' => ['sometimes', 'boolean']])->validate();
        $update->execute($schedule, $attributes);
        unset($this->scheduleEdits[$scheduleId]);
    }

    public function deleteSchedule(string $scheduleId, DeleteSchedule $delete): void
    {
        $schedule = BackupSchedule::query()->whereKey($scheduleId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($schedule);
        unset($this->scheduleEdits[$scheduleId]);
    }

    public function render(): View
    {
        return view('control-panel-backups-livewire::components.schedule-inventory', ['schedules' => BackupSchedule::query()->with('policy')->where('team_id', $this->teamId())->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
