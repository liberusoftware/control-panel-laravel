<section aria-labelledby="mail-inventory-heading"><h2 id="mail-inventory-heading">Mail accounts</h2>
    <label for="mail-search">Search mail accounts</label><input id="mail-search" type="search" wire:model.live.debounce.300ms="search"><ul>@forelse($items as $item)<li>{{ $item->address }} — {{ $item->status }}</li>@empty<li>No mail accounts found.</li>@endforelse</ul></section>
