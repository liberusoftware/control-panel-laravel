<section aria-labelledby="hosting-resources">
    <h2 id="hosting-resources">{{ __('Hosting resources') }}</h2>
    <h3>{{ __('Runtime versions') }}</h3>
    <ul>@forelse($runtimes as $runtime)<li wire:key="runtime-{{ $runtime->getKey() }}">{{ $runtime->runtime }} {{ $runtime->version }}</li>@empty<li>{{ __('No runtimes found.') }}</li>@endforelse</ul>
    <h3>{{ __('Web servers') }}</h3>
    <ul>@forelse($servers as $server)<li wire:key="server-{{ $server->getKey() }}">{{ $server->server }} {{ $server->version }} — {{ $server->status }}</li>@empty<li>{{ __('No web servers found.') }}</li>@endforelse</ul>
    <h3>{{ __('Virtual hosts') }}</h3>
    <ul>@forelse($virtualHosts as $virtualHost)<li wire:key="virtual-host-{{ $virtualHost->getKey() }}">{{ $virtualHost->domain->hostname }} — {{ $virtualHost->server }} <form wire:submit="updateVirtualHost('{{ $virtualHost->getKey() }}', null)"><input aria-label="{{ __('Virtual host document root') }}" wire:model="virtualHostEdits.{{ $virtualHost->getKey() }}.document_root" value="{{ $virtualHost->document_root }}"><button type="submit">{{ __('Save') }}</button></form> <button type="button" wire:click="deleteVirtualHost('{{ $virtualHost->getKey() }}')">{{ __('Delete') }}</button></li>@empty<li>{{ __('No virtual hosts found.') }}</li>@endforelse</ul>
    <h3>{{ __('SSL certificates') }}</h3>
    <ul>@forelse($certificates as $certificate)<li wire:key="certificate-{{ $certificate->getKey() }}">{{ $certificate->issuer }} — {{ $certificate->status }}</li>@empty<li>{{ __('No certificates found.') }}</li>@endforelse</ul>
    <h3>{{ __('Redirects') }}</h3>
    <ul>@forelse($redirects as $redirect)<li wire:key="redirect-{{ $redirect->getKey() }}">{{ $redirect->source }} → {{ $redirect->destination }}</li>@empty<li>{{ __('No redirects found.') }}</li>@endforelse</ul>
    <h3>{{ __('Applications') }}</h3>
    <ul>@forelse($applications as $application)<li wire:key="application-{{ $application->getKey() }}">{{ $application->name }} — {{ $application->status }} <form wire:submit="updateApplication('{{ $application->getKey() }}', null)"><input aria-label="{{ __('Application name') }}" wire:model="applicationEdits.{{ $application->getKey() }}.name" value="{{ $application->name }}"><input aria-label="{{ __('Document root') }}" wire:model="applicationEdits.{{ $application->getKey() }}.document_root" value="{{ $application->document_root }}"><button type="submit">{{ __('Save') }}</button></form> <button type="button" wire:click="checkApplication('{{ $application->getKey() }}')">{{ __('Check health') }}</button> <button type="button" wire:click="deleteApplication('{{ $application->getKey() }}')">{{ __('Delete') }}</button></li>@empty<li>{{ __('No applications found.') }}</li>@endforelse</ul>
    <h3>{{ __('Hosting logs') }}</h3>
    <ul>@forelse($logs as $log)<li wire:key="hosting-log-{{ $log->getKey() }}">{{ $log->level }} — {{ $log->message }}</li>@empty<li>{{ __('No hosting logs found.') }}</li>@endforelse</ul>
    {{ $logs->links() }}
</section>
