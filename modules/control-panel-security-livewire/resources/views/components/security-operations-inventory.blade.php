<section aria-labelledby="security-operations-inventory-heading">
    <h2 id="security-operations-inventory-heading">{{ __('Security operations') }}</h2>

    <h3>{{ __('Patches') }}</h3>
    <ul>
        @forelse ($patches as $patch)
            <li wire:key="security-patch-{{ $patch->getKey() }}">{{ $patch->package }} — {{ $patch->current_version ?: __('unknown') }} → {{ $patch->target_version }} ({{ $patch->status }})</li>
        @empty
            <li>{{ __('No patch records found.') }}</li>
        @endforelse
    </ul>

    <h3>{{ __('MFA and RBAC policies') }}</h3>
    <ul>
        @forelse ($policies as $policy)
            <li wire:key="security-policy-{{ $policy->getKey() }}">{{ $policy->subject_type }}:{{ $policy->subject_id }} — {{ $policy->mfa_required ? __('MFA required') : __('MFA optional') }} ({{ $policy->status }})</li>
        @empty
            <li>{{ __('No MFA/RBAC policies found.') }}</li>
        @endforelse
    </ul>

    <h3>{{ __('Secrets') }}</h3>
    <ul>
        @forelse ($secrets as $secret)
            <li wire:key="security-secret-{{ $secret->getKey() }}">{{ $secret->name }} — {{ $secret->purpose ?: __('unspecified purpose') }} ({{ $secret->status }})</li>
        @empty
            <li>{{ __('No secrets found.') }}</li>
        @endforelse
    </ul>

    <h3>{{ __('Malware scans') }}</h3>
    <ul>
        @forelse ($malwareScans as $scan)
            <li wire:key="security-malware-{{ $scan->getKey() }}">{{ $scan->scanner }} — {{ $scan->status }} ({{ $scan->subject_type }}:{{ $scan->subject_id }})</li>
        @empty
            <li>{{ __('No malware scans found.') }}</li>
        @endforelse
    </ul>

    <h3>{{ __('Intrusion controls') }}</h3>
    <ul>
        @forelse ($intrusionControls as $control)
            <li wire:key="security-intrusion-{{ $control->getKey() }}">{{ $control->kind }} — {{ $control->enabled ? __('enabled') : __('disabled') }}</li>
        @empty
            <li>{{ __('No intrusion controls found.') }}</li>
        @endforelse
    </ul>
</section>
