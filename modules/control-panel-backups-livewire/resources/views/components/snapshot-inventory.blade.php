<section>
    <h2>{{ __('Backup snapshots') }}</h2>
    <ul>
        @forelse ($snapshots as $snapshot)
            <li wire:key="snapshot-{{ $snapshot->getKey() }}">{{ $snapshot->location }} — {{ $snapshot->status->value }}</li>
        @empty
            <li>{{ __('No backup snapshots found.') }}</li>
        @endforelse
    </ul>
    {{ $snapshots->links() }}
</section>
