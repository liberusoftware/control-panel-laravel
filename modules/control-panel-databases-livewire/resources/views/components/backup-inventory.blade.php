<section aria-labelledby="database-backup-inventory">
    <h2 id="database-backup-inventory">{{ __('Database backups') }}</h2>
    <ul>
        @forelse ($backups as $backup)
            <li wire:key="database-backup-{{ $backup->getKey() }}">{{ $backup->database?->name }} — {{ $backup->status->value }}</li>
        @empty
            <li>{{ __('No database backups found.') }}</li>
        @endforelse
    </ul>
    {{ $backups->links() }}
</section>
