<section aria-labelledby="control-panel-account-feature-inventory">
    <h2 id="control-panel-account-feature-inventory">Account features</h2>
    <p>{{ $packages->count() }} packages and {{ $delegations->count() }} delegations.</p>
    <ul>
        @foreach ($delegations as $delegation)
            <li wire:key="delegation-{{ $delegation->getKey() }}">
                {{ $delegation->delegate_id }} — {{ $delegation->active ? 'active' : 'revoked' }}
                @if ($delegation->active)
                    <button type="button" wire:click="revokeDelegation('{{ $delegation->getKey() }}')">Revoke</button>
                @endif
            </li>
        @endforeach
    </ul>
</section>
