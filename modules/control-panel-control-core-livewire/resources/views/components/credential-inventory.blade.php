<section aria-labelledby="control-core-credential-inventory">
    <h2 id="control-core-credential-inventory">Node credentials</h2>

    @if ($credentials->isEmpty())
        <p>No managed node credentials are registered for the current team.</p>
    @else
        <ul>
            @foreach ($credentials as $credential)
                <li wire:key="credential-{{ $credential->getKey() }}">
                    <span>{{ $credential->name }}</span>
                    <span>{{ $credential->type }}</span>
                    <span>{{ $credential->status->value }}</span>
                    @if ($credential->status->value !== 'revoked')
                        <button type="button" wire:click="revoke('{{ $credential->getKey() }}')">{{ __('Revoke') }}</button>
                    @endif
                    @if ($credential->status->value === 'active' && $credential->expires_at?->isPast())
                        <button type="button" wire:click="expire('{{ $credential->getKey() }}')">{{ __('Expire') }}</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
