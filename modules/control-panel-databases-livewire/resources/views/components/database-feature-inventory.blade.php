<section>
    <h2>{{ __('Database operations and access') }}</h2>
    @foreach ($features as $label => $items)
        <h3>{{ __(ucfirst($label)) }}</h3>
        <ul>
            @forelse ($items as $item)
                <li wire:key="{{ str($label)->slug() }}-{{ $item->getKey() }}">
                    {{ $item->username ?? ($item->source_cidr ?? ($item->from_version ?? $item->database_id ?? $item->getKey())) }}
                    @if ($item->status ?? false) — {{ is_object($item->status) ? $item->status->value : $item->status }} @endif
                </li>
            @empty
                <li>{{ __('No records found.') }}</li>
            @endforelse
        </ul>
    @endforeach
</section>
