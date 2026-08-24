<section>
    <h2>{{ __('Certificates') }}</h2>
    <ul>
        @forelse ($certificates as $certificate)
            <li wire:key="certificate-{{ $certificate->getKey() }}">{{ implode(', ', $certificate->domains) }} — {{ $certificate->status->value }}</li>
        @empty
            <li>{{ __('No certificates found.') }}</li>
        @endforelse
    </ul>
    {{ $certificates->links() }}
</section>
