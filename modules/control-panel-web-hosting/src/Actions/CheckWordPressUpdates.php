<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;

final class CheckWordPressUpdates
{
    private const VERSION_ENDPOINT = 'https://api.wordpress.org/core/version-check/1.7/';

    /** @return array{current_version: ?string, latest_version: ?string, update_available: bool} */
    public function execute(HostedApplication $application): array
    {
        if ($application->type !== 'wordpress') {
            throw ValidationException::withMessages(['application' => 'The hosted application is not a WordPress application.']);
        }

        $currentVersion = $application->version;
        $latestVersion = null;

        try {
            $response = Http::timeout(10)->connectTimeout(3)->withUserAgent('Liberu Control Panel WordPress Update Check')->get(self::VERSION_ENDPOINT);
            if ($response->successful()) {
                $latestVersion = $response->json('offers.0.version');
            }
        } catch (\Throwable) {
            // A failed remote check must not change the application lifecycle state.
        }

        $config = $application->config ?? [];
        $config['last_update_check'] = now()->toIso8601String();
        $config['latest_version'] = is_string($latestVersion) ? $latestVersion : ($config['latest_version'] ?? null);
        $application->forceFill(['config' => $config])->save();

        return [
            'current_version' => $currentVersion,
            'latest_version' => is_string($latestVersion) ? $latestVersion : null,
            'update_available' => is_string($currentVersion) && is_string($latestVersion) && version_compare($currentVersion, $latestVersion, '<'),
        ];
    }
}
