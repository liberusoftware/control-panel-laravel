<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ControlCore\Actions\ReleaseOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\TransitionOperationTask;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Models\AuditEntry;
use Liberu\ControlPanel\ControlCore\Models\InventoryRecord;
use Liberu\ControlPanel\ControlCore\Models\OperationLock;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;
use Livewire\Component;

final class OperationsInventory extends Component
{
    public string $lockOwner = '';

    public function releaseLock(string $lockId, ReleaseOperationLock $release): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $lock = OperationLock::query()->whereKey($lockId)->where('team_id', $teamId)->firstOrFail();
        $this->validate(['lockOwner' => ['required', 'string', 'max:255']]);
        $release->execute($lock, $this->lockOwner);
        $this->reset('lockOwner');
    }

    public function transitionTask(string $taskId, string $status, TransitionOperationTask $transition): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $task = OperationTask::query()->whereKey($taskId)->where('team_id', $teamId)->firstOrFail();
        abort_unless(in_array($status, ['running', 'succeeded', 'failed', 'cancelled'], true), 422);
        $transition->execute($task, TaskStatus::from($status));
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-control-core-livewire::components.operations-inventory', [
            'tasks' => OperationTask::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
            'inventory' => InventoryRecord::query()->where('team_id', $teamId)->latest('observed_at')->limit(25)->get(),
            'locks' => OperationLock::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
            'audit' => AuditEntry::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
        ]);
    }
}
