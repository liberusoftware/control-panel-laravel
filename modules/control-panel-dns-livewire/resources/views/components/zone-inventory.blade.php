<section>
    <h2>{{ __('DNS zones') }}</h2>
    <ul>
        @forelse ($zones as $zone)
            <li wire:key="zone-{{ $zone->getKey() }}">{{ $zone->domain }} — {{ $zone->status->value }}</li>
        @empty
            <li>{{ __('No zones found.') }}</li>
        @endforelse
    </ul>
    {{ $zones->links() }}
</section>
