<section>
    <h2>{{ __('Files') }}</h2>
    <ul>
        @forelse ($files as $file)
            <li wire:key="file-{{ $file->getKey() }}">{{ $file->path }} — {{ $file->status->value }}</li>
        @empty
            <li>{{ __('No files found.') }}</li>
        @endforelse
    </ul>
    {{ $files->links() }}
</section>
