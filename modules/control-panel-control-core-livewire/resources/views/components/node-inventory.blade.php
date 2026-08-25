<section aria-labelledby="control-core-node-inventory">
    <h2 id="control-core-node-inventory">Node inventory</h2>

    <label for="node-search">Search nodes</label>
    <input id="node-search" type="search" wire:model.live.debounce.300ms="search">

    @if ($nodes->isEmpty())
        <p>No nodes are registered for the current team.</p>
    @else
        <ul>
            @foreach ($nodes as $node)
                <li wire:key="node-{{ $node->getKey() }}">
                    <span>{{ $node->name }}</span>
                    <span>{{ $node->hostname }}</span>
                    <span>{{ $node->status->value }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</section>
