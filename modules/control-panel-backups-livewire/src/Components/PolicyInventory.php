<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Backups\Actions\DeletePolicy;
use Liberu\ControlPanel\Backups\Actions\UpdatePolicy;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Livewire\Component;

final class PolicyInventory extends Component
{
    public int $perPage = 25;

    /** @var array<string, array<string, mixed>> */
    public array $policyEdits = [];

    /** @param array<string, mixed>|null $attributes */
    public function updatePolicy(string $policyId, ?array $attributes, UpdatePolicy $update): void
    {
        $policy = BackupPolicy::query()->whereKey($policyId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->policyEdits[$policyId] ?? [];
        validator($attributes, ['name' => ['required', 'string', 'max:160'], 'storage_driver' => ['required', 'string', 'max:80'], 'retention_days' => ['required', 'integer', 'min:1'], 'active' => ['sometimes', 'boolean']])->validate();
        $update->execute($policy, $attributes);
        unset($this->policyEdits[$policyId]);
    }

    public function deletePolicy(string $policyId, DeletePolicy $delete): void
    {
        $policy = BackupPolicy::query()->whereKey($policyId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($policy);
        unset($this->policyEdits[$policyId]);
    }

    public function render(): View
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');

        return view('control-panel-backups-livewire::components.policy-inventory', ['policies' => BackupPolicy::query()->where('team_id', auth()->user()->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
