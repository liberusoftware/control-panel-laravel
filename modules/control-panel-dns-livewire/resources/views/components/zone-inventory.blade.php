<section>
    <h2>{{ __('DNS zones') }}</h2>
    <label for="zone-search">{{ __("Search zones") }}</label><input id="zone-search" type="search" wire:model.live.debounce.300ms="search">
    <ul>
        @forelse ($zones as $zone)
            <li wire:key="zone-{{ $zone->getKey() }}">{{ $zone->domain }} — {{ $zone->status->value }}</li>
        @empty
            <li>{{ __('No zones found.') }}</li>
        @endforelse
    </ul>
    {{ $zones->links() }}
</section>
