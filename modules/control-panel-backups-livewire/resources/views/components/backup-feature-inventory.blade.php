<section>
    <h2>{{ __('Backup operations') }}</h2>
    @foreach ([__('Encryption') => $encryptions, __('Restores') => $restores, __('Off-site transfers') => $transfers] as $label => $items)
        <h3>{{ $label }}</h3>
        <ul>
            @forelse ($items as $item)
                <li wire:key="{{ str($label)->slug() }}-{{ $item->getKey() }}">
                    {{ $item->policy_id ?? ($item->snapshot_id ?? $item->destination_id ?? $item->getKey()) }}
                    @if ($item->status ?? false) — {{ is_object($item->status) ? $item->status->value : $item->status }} @endif
                </li>
            @empty
                <li>{{ __('No records found.') }}</li>
            @endforelse
        </ul>
    @endforeach
</section>
