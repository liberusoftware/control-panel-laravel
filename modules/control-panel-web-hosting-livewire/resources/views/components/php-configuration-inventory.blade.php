<section aria-labelledby="web-hosting-php-configuration-inventory">
    <h2 id="web-hosting-php-configuration-inventory">PHP configurations</h2>
    @if ($configurations->isEmpty())
        <p>No PHP configurations are saved.</p>
    @else
        <ul>
            @foreach ($configurations as $configuration)
                <li>{{ $configuration->domain?->hostname }} — PHP {{ $configuration->php_version }} — {{ $configuration->memory_limit }} MB</li>
            @endforeach
        </ul>
        {{ $configurations->links() }}
    @endif
</section>
