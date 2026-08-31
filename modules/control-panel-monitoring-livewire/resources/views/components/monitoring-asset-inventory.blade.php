<section>
    <h2>{{ __('Monitoring assets') }}</h2>
    @foreach ($assets as $label => $items)
        <h3>{{ __(ucfirst($label)) }}</h3>
        <ul>
            @forelse ($items as $item)
                <li wire:key="{{ str($label)->slug() }}-{{ $item->getKey() }}">
                    {{ $item->name ?? ($item->title ?? ($item->component ?? ($item->resource ?? ($item->source ?? $item->getKey())))) }}
                    @if ($item->status ?? false) — {{ is_object($item->status) ? $item->status->value : $item->status }} @endif
                </li>
            @empty
                <li>{{ __('No records found.') }}</li>
            @endforelse
        </ul>
    @endforeach
</section>
