<section aria-labelledby="control-core-node-inventory">
    <h2 id="control-core-node-inventory">Node inventory</h2>

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
