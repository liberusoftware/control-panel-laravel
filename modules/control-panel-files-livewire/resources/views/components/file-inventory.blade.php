<section>
    <h2>{{ __('Files') }}</h2>
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search files') }}">
    <ul>
        @forelse ($files as $file)
            <li wire:key="file-{{ $file->getKey() }}">{{ $file->path }} — {{ $file->status->value }} @if ($file->status->value !== 'retained') <button type="button" wire:click="delete('{{ $file->getKey() }}')">{{ __('Delete') }}</button> @endif</li>
        @empty
            <li>{{ __('No files found.') }}</li>
        @endforelse
    </ul>
    {{ $files->links() }}
</section>
