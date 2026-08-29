<section aria-labelledby="api-automation-credential-inventory">
    <h2 id="api-automation-credential-inventory">API credentials</h2>

    @if ($credentials->isEmpty())
        <p>No API credentials are registered for the current team.</p>
    @else
        <ul>
            @foreach ($credentials as $credential)
                <li wire:key="api-credential-{{ $credential->getKey() }}">
                    <span>{{ $credential->name }}</span>
                    <span>{{ $credential->status }}</span>
                    @if ($credential->status === 'active')
                        <button type="button" wire:click="revoke('{{ $credential->getKey() }}')">{{ __('Revoke') }}</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
