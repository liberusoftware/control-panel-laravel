<section aria-labelledby="api-automation-feature-inventory">
    <h2 id="api-automation-feature-inventory">Automation features</h2>
    <p wire:loading role="status">Loading automation features…</p>
    <p>{{ $templates->count() }} templates, {{ $schedules->count() }} schedules, {{ $commands->count() }} commands, {{ $events->count() }} billing events.</p>

    @if ($templates->isEmpty() && $schedules->isEmpty() && $commands->isEmpty() && $events->isEmpty())
        <p>No automation features are available for the current team.</p>
    @endif
</section>
