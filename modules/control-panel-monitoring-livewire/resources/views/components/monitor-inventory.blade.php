<section aria-labelledby="monitor-inventory-heading"><h2 id="monitor-inventory-heading">Monitors</h2>
    <label for="monitor-search">Search monitors</label><input id="monitor-search" type="search" wire:model.live.debounce.300ms="search"><ul>@forelse($items as $item)<li>{{ $item->name }} — {{ $item->status }}</li>@empty<li>No monitors found.</li>@endforelse</ul></section>
