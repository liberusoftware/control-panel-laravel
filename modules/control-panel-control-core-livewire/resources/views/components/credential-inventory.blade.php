<section aria-labelledby="control-core-credential-inventory">
    <h2 id="control-core-credential-inventory">Node credentials</h2>

    <form wire:submit="createCredential" aria-labelledby="control-core-credential-create">
        <h3 id="control-core-credential-create">Register an SSH public key</h3>
        <label>
            {{ __('Node ID') }}
            <input type="text" wire:model="nodeId" required>
        </label>
        @error('nodeId') <p role="alert">{{ $message }}</p> @enderror
        <label>
            {{ __('Name') }}
            <input type="text" wire:model="name" required maxlength="160">
        </label>
        @error('name') <p role="alert">{{ $message }}</p> @enderror
        <label>
            {{ __('Username') }}
            <input type="text" wire:model="username" maxlength="120">
        </label>
        @error('username') <p role="alert">{{ $message }}</p> @enderror
        <label>
            {{ __('SSH public key') }}
            <textarea wire:model="publicKey" required maxlength="10000"></textarea>
        </label>
        @error('publicKey') <p role="alert">{{ $message }}</p> @enderror
        <button type="submit">{{ __('Register credential') }}</button>
    </form>

    @if ($credentials->isEmpty())
        <p>No managed node credentials are registered for the current team.</p>
    @else
        <ul>
            @foreach ($credentials as $credential)
                <li wire:key="credential-{{ $credential->getKey() }}">
                    <form wire:submit="update('{{ $credential->getKey() }}', null)">
                        <input type="text" wire:model="edits.{{ $credential->getKey() }}.name" value="{{ $credential->name }}" maxlength="160" required>
                        <button type="submit">{{ __('Save') }}</button>
                    </form>
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
