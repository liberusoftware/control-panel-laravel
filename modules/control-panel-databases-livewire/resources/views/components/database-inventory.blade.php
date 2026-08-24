<section>
    <h2>{{ __('Databases') }}</h2>
    <ul>
        @forelse ($databases as $database)
            <li wire:key="database-{{ $database->getKey() }}">{{ $database->name }} — {{ $database->status->value }}</li>
        @empty
            <li>{{ __('No databases found.') }}</li>
        @endforelse
    </ul>
    {{ $databases->links() }}
</section>
