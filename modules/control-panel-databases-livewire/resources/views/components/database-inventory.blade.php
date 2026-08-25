<section>
    <h2>{{ __('Databases') }}</h2>
    <label for="database-search">{{ __("Search databases") }}</label><input id="database-search" type="search" wire:model.live.debounce.300ms="search">
    <ul>
        @forelse ($databases as $database)
            <li wire:key="database-{{ $database->getKey() }}">
                {{ $database->name }} — {{ $database->status->value }}
                @if ($database->status->value !== 'active' && $database->status->value !== 'archived')
                    <button type="button" wire:click="activate('{{ $database->getKey() }}')">{{ __('Activate') }}</button>
                @endif
            </li>
        @empty
            <li>{{ __('No databases found.') }}</li>
        @endforelse
    </ul>
    {{ $databases->links() }}
</section>
