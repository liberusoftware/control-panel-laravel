<section aria-labelledby="automation-inventory-heading"><h2 id="automation-inventory-heading">Automations</h2>
    <label for="automation-search">Search automations</label><input id="automation-search" type="search" wire:model.live.debounce.300ms="search"><ul>@forelse($items as $item)<li>{{ $item->name }} — {{ $item->status }}</li>@empty<li>No automations found.</li>@endforelse</ul></section>
