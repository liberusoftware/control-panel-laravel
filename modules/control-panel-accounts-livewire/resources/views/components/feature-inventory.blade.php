<section aria-labelledby="control-panel-account-feature-inventory">
    <h2 id="control-panel-account-feature-inventory">Account features</h2>
    <p>{{ $packages->count() }} packages, {{ $assignments->count() }} package assignments, and {{ $delegations->count() }} delegations.</p>
    <ul>
        @foreach ($packages as $package)
            <li wire:key="package-{{ $package->getKey() }}">
                <form wire:submit="updatePackage('{{ $package->getKey() }}', null)" class="inline-flex gap-2">
                    <label>
                        <span class="sr-only">Package name</span>
                        <input type="text" wire:model="packageEdits.{{ $package->getKey() }}.name" value="{{ $package->name }}" maxlength="160" required>
                    </label>
                    <label>
                        <span>Active</span>
                        <input type="checkbox" wire:model="packageEdits.{{ $package->getKey() }}.active" @checked($package->active)>
                    </label>
                    <button type="submit">Save package</button>
                </form>
            </li>
        @endforeach
    </ul>
    <ul>
        @foreach ($assignments as $assignment)
            <li wire:key="assignment-{{ $assignment->getKey() }}">
                {{ $assignment->account?->name }} — {{ $assignment->hostingPackage?->name }}
                ({{ $assignment->start_date?->toDateString() }}{{ $assignment->end_date ? ' to '.$assignment->end_date->toDateString() : '' }})
                — {{ $assignment->active ? 'active' : 'inactive' }}
                <form wire:submit="updateAssignment('{{ $assignment->getKey() }}', null)" class="inline-flex gap-2">
                    <label><span class="sr-only">Start date</span><input type="date" wire:model="assignmentEdits.{{ $assignment->getKey() }}.start_date" value="{{ $assignment->start_date?->toDateString() }}" required></label>
                    <label><span class="sr-only">End date</span><input type="date" wire:model="assignmentEdits.{{ $assignment->getKey() }}.end_date" value="{{ $assignment->end_date?->toDateString() }}"></label>
                    <label><span>Active</span><input type="checkbox" wire:model="assignmentEdits.{{ $assignment->getKey() }}.active" @checked($assignment->active)></label>
                    <button type="submit">Save assignment</button>
                </form>
                <button type="button" wire:click="removeAssignment('{{ $assignment->getKey() }}')">Remove</button>
            </li>
        @endforeach
    </ul>
    <ul>
        @foreach ($delegations as $delegation)
            <li wire:key="delegation-{{ $delegation->getKey() }}">
                {{ $delegation->delegate_id }} — {{ $delegation->active ? 'active' : 'revoked' }}
                <form wire:submit="updateDelegation('{{ $delegation->getKey() }}', null)" class="inline-flex gap-2">
                    <label>
                        <span class="sr-only">Delegate</span>
                        <input type="text" wire:model="delegationEdits.{{ $delegation->getKey() }}.delegate_id" value="{{ $delegation->delegate_id }}" maxlength="255">
                    </label>
                    <label>
                        <span>Active</span>
                        <input type="checkbox" wire:model="delegationEdits.{{ $delegation->getKey() }}.active" @checked($delegation->active)>
                    </label>
                    <button type="submit">Save</button>
                </form>
                @if ($delegation->active)
                    <button type="button" wire:click="revokeDelegation('{{ $delegation->getKey() }}')">Revoke</button>
                @endif
            </li>
        @endforeach
    </ul>
</section>
