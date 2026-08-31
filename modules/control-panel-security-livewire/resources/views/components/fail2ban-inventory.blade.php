<section aria-labelledby="fail2ban-inventory-heading">
    <h2 id="fail2ban-inventory-heading">{{ __('Fail2ban') }}</h2>
    <h3>{{ __('Jails') }}</h3>
    <ul>@forelse($settings as $setting)<li wire:key="fail2ban-setting-{{ $setting->getKey() }}">{{ $setting->jail_name }} — {{ $setting->enabled ? __('enabled') : __('disabled') }} ({{ $setting->max_retry }} retries / {{ $setting->find_time }}s)</li>@empty<li>{{ __('No Fail2ban jails found.') }}</li>@endforelse</ul>
    <h3>{{ __('Active bans') }}</h3>
    <ul>@forelse($bans as $ban)<li wire:key="fail2ban-ban-{{ $ban->getKey() }}">{{ $ban->ip_address }} — {{ $ban->jail_name }} @if($ban->reason) — {{ $ban->reason }} @endif <button type="button" wire:click="unban('{{ $ban->getKey() }}')">{{ __('Unban') }}</button></li>@empty<li>{{ __('No active bans found.') }}</li>@endforelse</ul>
</section>
