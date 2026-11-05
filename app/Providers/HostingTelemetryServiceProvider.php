<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class HostingTelemetryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! config('telescope.enabled')) {
            return;
        }
        Telescope::hideRequestHeaders(['authorization', 'proxy-authorization', 'x-api-key', 'private-token', 'x-gitlab-token', 'x-auth-token', 'cookie', 'set-cookie']);
        Telescope::filter(static function (IncomingEntry $entry): bool {
            if (! $entry->isClientRequest()) {
                return true;
            }
            $uri = $entry->content['uri'] ?? '';
            $path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : false;

            // Management responses may contain base64 Kubernetes Secrets or source-account passwords.
            // Match proxy-prefixed paths too; do not capture request, user or tenant state in this filter.
            return ! is_string($path) || ! preg_match('~/(?:api/v1/(?:namespaces|servers)(?:/|$)|apis(?:/|$)|virtual-server/remote\.cgi(?:/|$)|jmap(?:/|$))~', $path);
        });
    }
}
