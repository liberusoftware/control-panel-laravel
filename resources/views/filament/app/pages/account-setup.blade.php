<x-filament-panels::page>
    <div class="mx-auto w-full max-w-5xl space-y-6">
        <div>
            <p class="text-sm font-medium text-primary-600">Welcome to your workspace</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Finish setting up your account</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">A few short steps make your team ready for daily operations. You can return here whenever you need to change an integration.</p>
        </div>

        <nav aria-label="Setup progress" class="grid gap-3 md:grid-cols-3">
            @foreach ([1 => 'Team profile', 2 => 'Integrations', 3 => 'Ready to go'] as $number => $label)
                <button type="button" wire:click="goToStep({{ $number }})" @disabled($number > 1 && ! in_array($number - 1, $completedSteps, true)) class="rounded-xl border p-4 text-left transition hover:border-primary-500 disabled:cursor-not-allowed disabled:opacity-50 {{ $step === $number ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30' : 'border-gray-200 dark:border-gray-700' }}">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Step {{ $number }}</span>
                    <span class="mt-1 block font-medium">{{ $label }}</span>
                    @if (in_array($number, $completedSteps, true))
                        <span class="mt-1 block text-xs text-success-600">Complete</span>
                    @endif
                </button>
            @endforeach
        </nav>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @if ($step === 1)
                <form wire:submit="saveProfile" class="space-y-6">
                    <div><h2 class="text-lg font-semibold">Tell us about your team</h2><p class="mt-1 text-sm text-gray-600 dark:text-gray-400">This information personalises the workspace for everyone on your team.</p></div>
                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block text-sm font-medium">Team name<input wire:model="teamName" required maxlength="255" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"></label>
                        <label class="block text-sm font-medium">Workspace timezone<input wire:model="timezone" required placeholder="UTC" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"></label>
                    </div>
                    @error('teamName')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                    @error('timezone')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                    <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500" wire:loading.attr="disabled">Save and continue</button>
                </form>
            @elseif ($step === 2)
                <form wire:submit="saveIntegrations" class="space-y-6">
                    <div><h2 class="text-lg font-semibold">Connect the services you use</h2><p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Credentials are encrypted before storage and are never shown again after saving. Leave a provider blank if you do not use it.</p></div>
                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block text-sm font-medium">GitHub OAuth client ID<input wire:model="githubClientId" maxlength="255" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"></label>
                        <label class="block text-sm font-medium">GitHub OAuth client secret<input wire:model="githubClientSecret" type="password" maxlength="1000" autocomplete="new-password" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"></label>
                        <label class="block text-sm font-medium">Google OAuth client ID<input wire:model="googleClientId" maxlength="255" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"></label>
                        <label class="block text-sm font-medium">Google OAuth client secret<input wire:model="googleClientSecret" type="password" maxlength="1000" autocomplete="new-password" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"></label>
                        <label class="block text-sm font-medium md:col-span-2">Stripe secret key<input wire:model="stripeSecret" type="password" maxlength="1000" autocomplete="new-password" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"></label>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-800"><span class="font-medium">Current status</span><dl class="mt-2 grid gap-1 md:grid-cols-3">@foreach ($this->integrationStatus() as $name => $configured)<div><dt class="inline">{{ $name }}:</dt> <dd class="inline">{{ $configured ? 'Configured' : 'Not configured' }}</dd></div>@endforeach</dl></div>
                    <div class="flex gap-3"><button type="button" wire:click="goToStep(1)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold dark:border-gray-600">Back</button><button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500" wire:loading.attr="disabled">Save and continue</button></div>
                </form>
            @else
                <div class="space-y-6"><div><h2 class="text-lg font-semibold">Your workspace is ready</h2><p class="mt-1 text-sm text-gray-600 dark:text-gray-400">You can manage team members, security, and integrations from the Account menu at any time.</p></div><div class="rounded-lg bg-success-50 p-4 text-sm text-success-800 dark:bg-success-950/30 dark:text-success-200">Setup is complete for <strong>{{ $teamName }}</strong>.</div><div class="flex gap-3"><button type="button" wire:click="goToStep(2)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold dark:border-gray-600">Review integrations</button><button type="button" wire:click="finish" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500" wire:loading.attr="disabled">Go to dashboard</button></div></div>
            @endif
            <p wire:loading role="status" class="text-sm text-gray-500">Saving your setup…</p>
        </section>
    </div>
</x-filament-panels::page>
