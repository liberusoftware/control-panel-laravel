<section>
    <h2>{{ __('Backup snapshots') }}</h2>
    <ul>
        @forelse ($snapshots as $snapshot)
            <li wire:key="snapshot-{{ $snapshot->getKey() }}">{{ $snapshot->location }} — {{ $snapshot->status->value }} <label for="snapshot-checksum-{{ $snapshot->getKey() }}">{{ __('Checksum') }}</label><input id="snapshot-checksum-{{ $snapshot->getKey() }}" type="text" wire:model="checksum"><button type="button" wire:click="verify('{{ $snapshot->getKey() }}')">{{ __('Verify') }}</button> @if ($snapshot->status->value === 'verified') <label for="restore-target-{{ $snapshot->getKey() }}">{{ __('Restore target') }}</label><input id="restore-target-{{ $snapshot->getKey() }}" type="text" wire:model="restoreTarget"><button type="button" wire:click="restore('{{ $snapshot->getKey() }}')">{{ __('Restore') }}</button> @endif</li>
        @empty
            <li>{{ __('No backup snapshots found.') }}</li>
        @endforelse
    </ul>
    {{ $snapshots->links() }}
</section>
