<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Accounts\Actions\AssignHostingPackage;
use Liberu\ControlPanel\Accounts\Actions\RemoveHostingPackageAssignment;
use Liberu\ControlPanel\Accounts\Actions\RevokeDelegation;
use Liberu\ControlPanel\Accounts\Actions\UpdateDelegation;
use Liberu\ControlPanel\Accounts\Actions\UpdateHostingPackage;
use Liberu\ControlPanel\Accounts\Actions\UpdateHostingPackageAssignment;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\Accounts\Models\AccountDelegation;
use Liberu\ControlPanel\Accounts\Models\HostingPackage;
use Liberu\ControlPanel\Accounts\Models\HostingPackageAssignment;
use Livewire\Component;

final class AccountFeatureInventory extends Component
{
    /** @var array<string, array<string, mixed>> */
    public array $delegationEdits = [];

    /** @var array<string, array<string, mixed>> */
    public array $packageEdits = [];

    /** @var array<string, array<string, mixed>> */
    public array $assignmentEdits = [];

    /** @param array<string, mixed> $attributes */
    public function assignPackage(string $accountId, array $attributes, AssignHostingPackage $assign): void
    {
        $account = Account::query()->whereKey($accountId)->where('team_id', $this->teamId())->firstOrFail();
        $data = validator($attributes, [
            'hosting_package_id' => ['required', 'uuid'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
        ])->validate();
        $package = HostingPackage::query()->whereKey($data['hosting_package_id'])->where('team_id', $this->teamId())->firstOrFail();
        $assign->execute($account, $package, $data);
    }

    /** @param array<string, mixed>|null $attributes */
    public function updateAssignment(string $assignmentId, ?array $attributes, UpdateHostingPackageAssignment $update): void
    {
        $assignment = HostingPackageAssignment::query()->whereKey($assignmentId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->assignmentEdits[$assignmentId] ?? [];
        $attributes = validator($attributes, [
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
        ])->validate();
        $update->execute($assignment, $attributes);
        unset($this->assignmentEdits[$assignmentId]);
    }

    public function removeAssignment(string $assignmentId, RemoveHostingPackageAssignment $remove): void
    {
        $assignment = HostingPackageAssignment::query()->whereKey($assignmentId)->where('team_id', $this->teamId())->firstOrFail();
        $remove->execute($assignment);
    }

    /** @param array<string, mixed>|null $attributes */
    public function updatePackage(string $packageId, ?array $attributes, UpdateHostingPackage $update): void
    {
        $package = HostingPackage::query()
            ->whereKey($packageId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();
        $attributes ??= $this->packageEdits[$packageId] ?? [];
        $attributes = validator($attributes, [
            'name' => ['required', 'string', 'max:160'],
            'limits' => ['nullable', 'array'],
            'features' => ['nullable', 'array'],
            'active' => ['sometimes', 'boolean'],
        ])->validate();
        $update->execute($package, $attributes);
        unset($this->packageEdits[$packageId]);
    }

    public function revokeDelegation(string $delegationId, RevokeDelegation $revoke): void
    {
        $delegation = AccountDelegation::query()
            ->whereKey($delegationId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();
        $revoke->execute($delegation);
    }

    /** @param array<string, mixed>|null $attributes */
    public function updateDelegation(string $delegationId, ?array $attributes, UpdateDelegation $update): void
    {
        $delegation = AccountDelegation::query()
            ->with('account')
            ->whereKey($delegationId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();
        $attributes ??= $this->delegationEdits[$delegationId] ?? [];
        $attributes = validator($attributes, [
            'delegate_id' => ['sometimes', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'expires_at' => ['nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
        ])->validate();
        $update->execute($delegation, $attributes);
        unset($this->delegationEdits[$delegationId]);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-accounts-livewire::components.feature-inventory', [
            'packages' => HostingPackage::where('team_id', $teamId)->latest()->limit(25)->get(),
            'delegations' => AccountDelegation::where('team_id', $teamId)->latest()->limit(25)->get(),
            'assignments' => HostingPackageAssignment::with(['account', 'hostingPackage'])->where('team_id', $teamId)->latest('start_date')->limit(25)->get(),
        ]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
