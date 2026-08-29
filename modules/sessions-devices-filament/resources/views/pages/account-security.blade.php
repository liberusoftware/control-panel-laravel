<x-filament-panels::page>
    <section aria-labelledby="account-preferences-heading" class="space-y-3">
        <h2 id="account-preferences-heading" class="text-lg font-semibold">Preferences</h2>
        <dl class="grid gap-2 sm:grid-cols-2">
            <div><dt class="font-medium">Locale</dt><dd>{{ auth()->user()->locale ?? app()->getLocale() }}</dd></div>
            <div><dt class="font-medium">Timezone</dt><dd>{{ auth()->user()->timezone ?? 'UTC' }}</dd></div>
            <div><dt class="font-medium">Appearance</dt><dd>{{ auth()->user()->theme_preference ?? 'Site default' }}</dd></div>
            <div><dt class="font-medium">Currency</dt><dd>{{ config('currency.display', config('currency.base')) }}</dd></div>
        </dl>
    </section>

    <section aria-labelledby="sessions-heading" class="space-y-3">
        <h2 id="sessions-heading" class="text-lg font-semibold">Active sessions</h2>
        <ul class="divide-y" role="list">
            @forelse ($sessions as $session)
                <li class="flex items-center justify-between gap-4 py-3">
                    <span><strong>{{ $session->is_current ? 'Current session' : 'Session' }}</strong><br>{{ $session->ip_address ?? 'Unknown network' }} · {{ date('Y-m-d H:i', $session->last_activity) }} UTC</span>
                    @unless ($session->is_current)<button type="button" wire:click="revoke('{{ $session->id }}')" class="fi-btn">Revoke</button>@endunless
                </li>
            @empty
                <li class="py-3">No persisted sessions were found.</li>
            @endforelse
        </ul>
    </section>

    <section aria-labelledby="tokens-heading" class="space-y-3">
        <h2 id="tokens-heading" class="text-lg font-semibold">Personal access tokens</h2>
        <form wire:submit="createToken" class="space-y-3">
            <label class="block">Name
                <input wire:model="tokenName" type="text" maxlength="255" required class="fi-input" />
            </label>
            <fieldset>
                <legend class="font-medium">Abilities</legend>
                @foreach (['read', 'create', 'update', 'delete'] as $ability)
                    <label class="mr-3"><input wire:model="tokenAbilities" type="checkbox" value="{{ $ability }}" /> {{ ucfirst($ability) }}</label>
                @endforeach
            </fieldset>
            <button type="submit" class="fi-btn">Create token</button>
        </form>

        @if ($newToken)
            <p role="status">Copy this token now; it will not be shown again:</p>
            <code class="block break-all" data-token="personal-access-token">{{ $newToken }}</code>
        @endif

        <ul class="divide-y" role="list">
            @forelse ($tokens as $token)
                <li class="flex items-center justify-between gap-4 py-3">
                    <span><strong>{{ $token['name'] }}</strong><br>{{ implode(', ', $token['abilities']) }} · expires {{ $token['expires_at'] ?? 'unknown' }}</span>
                    <button type="button" wire:click="revokeToken('{{ $token['id'] }}')" class="fi-btn">Revoke</button>
                </li>
            @empty
                <li class="py-3">No personal access tokens exist.</li>
            @endforelse
        </ul>
    </section>

    <p>Use the account security actions to update your password, configure two-factor authentication, review API tokens, or delete your account. Sensitive changes require recent authentication.</p>
</x-filament-panels::page>
