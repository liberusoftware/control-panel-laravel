<section>
    <h2>{{ __('Databases') }}</h2>
    <label for="database-search">{{ __("Search databases") }}</label><input id="database-search" type="search" wire:model.live.debounce.300ms="search">
    <ul>
        @forelse ($databases as $database)
            <li wire:key="database-{{ $database->getKey() }}">
                {{ $database->name }} — {{ $database->status->value }}
                <form wire:submit="update('{{ $database->getKey() }}', null)">
                    <input aria-label="{{ __('Database name') }}" wire:model="edits.{{ $database->getKey() }}.name" value="{{ $database->name }}">
                    <input aria-label="{{ __('Engine ID') }}" wire:model="edits.{{ $database->getKey() }}.engine_id" value="{{ $database->engine_id }}">
                    <input aria-label="{{ __('Charset') }}" wire:model="edits.{{ $database->getKey() }}.charset" value="{{ $database->charset }}">
                    <input aria-label="{{ __('Collation') }}" wire:model="edits.{{ $database->getKey() }}.collation" value="{{ $database->collation }}">
                    <button type="submit">{{ __('Save') }}</button>
                </form>
                @if ($database->status->value !== 'active' && $database->status->value !== 'archived')
                    <button type="button" wire:click="activate('{{ $database->getKey() }}')">{{ __('Activate') }}</button>
                @endif
                @if ($database->status->value === 'active')
                    <button type="button" wire:click="suspend('{{ $database->getKey() }}')">{{ __('Suspend') }}</button>
                @endif
                @if ($database->status->value !== 'archived')
                    <button type="button" wire:click="archive('{{ $database->getKey() }}')">{{ __('Archive') }}</button>
                @endif
                <button type="button" wire:click="delete('{{ $database->getKey() }}')" wire:confirm="{{ __('Delete this database permanently?') }}">{{ __('Delete') }}</button>
            </li>
        @empty
            <li>{{ __('No databases found.') }}</li>
        @endforelse
    </ul>
    {{ $databases->links() }}
</section>
