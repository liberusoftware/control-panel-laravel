<section aria-labelledby="control-panel-account-feature-inventory">
    <h2 id="control-panel-account-feature-inventory">Account features</h2>
    <p>{{ $packages->count() }} packages and {{ $delegations->count() }} delegations.</p>
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
