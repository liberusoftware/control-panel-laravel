<section>
    <h2>{{ $featureName }}</h2>

    <div>
        @forelse ($items as $item)
            <article wire:key="dns-feature-{{ $item->getKey() }}">
                @foreach ($columns as $column)
                    <span>{{ is_scalar($item->{$column}) || $item->{$column} === null ? ($item->{$column} ?? '—') : json_encode($item->{$column}) }}</span>
                @endforeach
            </article>
        @empty
            <p>{{ __('No records found.') }}</p>
        @endforelse
    </div>

    {{ $items->links() }}
</section>
