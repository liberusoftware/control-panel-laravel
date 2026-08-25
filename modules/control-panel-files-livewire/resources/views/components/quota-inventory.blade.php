<section>
    <h2>{{ __('File quotas') }}</h2>
    <form wire:submit="save">
        <input type="text" wire:model="ownerId" placeholder="{{ __('Owner ID (optional)') }}">
        <input type="number" wire:model="limitBytes" min="0" required>
        <button type="submit" wire:loading.attr="disabled">{{ __('Save quota') }}</button>
    </form>
    <ul>
        @forelse ($quotas as $quota)
            <li wire:key="quota-{{ $quota->getKey() }}">{{ $quota->owner_id ?? __('Team') }} — {{ $quota->used_bytes }} / {{ $quota->limit_bytes }}</li>
        @empty
            <li>{{ __('No quotas configured.') }}</li>
        @endforelse
    </ul>
    {{ $quotas->links() }}
</section>
