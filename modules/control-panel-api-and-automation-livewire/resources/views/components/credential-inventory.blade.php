<section aria-labelledby="api-automation-credential-inventory">
    <h2 id="api-automation-credential-inventory">API credentials</h2>

    @if ($credentials->isEmpty())
        <p>No API credentials are registered for the current team.</p>
    @else
        <ul>
            @foreach ($credentials as $credential)
                <li wire:key="api-credential-{{ $credential->getKey() }}">
                    <form wire:submit="update('{{ $credential->getKey() }}', null)" class="inline-flex gap-2">
                        <input type="text" wire:model="edits.{{ $credential->getKey() }}.name" value="{{ $credential->name }}" maxlength="120" required>
                        <button type="submit">{{ __('Save') }}</button>
                    </form>
                    <span>{{ $credential->status }}</span>
                    @if ($credential->status === 'active')
                        <button type="button" wire:click="revoke('{{ $credential->getKey() }}')">{{ __('Revoke') }}</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
