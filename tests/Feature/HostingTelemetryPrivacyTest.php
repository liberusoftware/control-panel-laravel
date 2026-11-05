<?php

use App\Providers\HostingTelemetryServiceProvider;
use Illuminate\Support\Facades\Http;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\ClientRequestWatcher;

beforeEach(function (): void {
    $this->telescopeState = [Telescope::$filterUsing, Telescope::$entriesQueue, Telescope::$shouldRecord, Telescope::$hiddenRequestHeaders];
    Telescope::$filterUsing = [];
    Telescope::$entriesQueue = [];
    config(['telescope.enabled' => true]);
    (new HostingTelemetryServiceProvider(app()))->boot();
    Telescope::startRecording(false);
});

afterEach(function (): void {
    [Telescope::$filterUsing, Telescope::$entriesQueue, Telescope::$shouldRecord, Telescope::$hiddenRequestHeaders] = $this->telescopeState;
});

it('does not retain sensitive hosting request or response content even when another filter allows all recording', function (string $uri): void {
    Telescope::filter(static fn (IncomingEntry $entry): bool => true);
    Telescope::recordClientRequest(IncomingEntry::make(['uri' => $uri, 'response' => ['data' => ['password' => 'private-response-password']], 'payload' => ['password' => 'private-request-password']]));
    expect(Telescope::$entriesQueue)->toBe([]);
})->with([
    'kubernetes secret' => 'https://cluster.example/api/v1/namespaces/tenant/secrets/app',
    'proxied kubernetes secret' => 'https://cluster.example/proxy/api/v1/namespaces/tenant/secrets/app',
    'database operator' => 'https://cluster.example/apis/k8s.mariadb.com/v1alpha1/namespaces/data/mariadbs/app',
    'powerdns' => 'https://dns.example/api/v1/servers/localhost/zones/example.test',
    'stalwart' => 'https://mail.example/jmap',
    'proxied stalwart' => 'https://mail.example/admin/jmap',
    'virtualmin' => 'https://source.example:10000/virtual-server/remote.cgi',
]);

it('redacts credentials through the actual client request watcher', function (): void {
    (new ClientRequestWatcher())->register(app());
    Http::fake(['https://public.example/status' => Http::response(['healthy' => true])]);
    Http::withToken('private-bearer-token')->withHeaders(['X-API-Key' => 'private-api-key'])->get('https://public.example/status');
    $entries = collect(Telescope::$entriesQueue)->filter(fn (IncomingEntry $entry): bool => $entry->isClientRequest());
    expect($entries)->toHaveCount(1)
        ->and(json_encode($entries))->not->toContain('private-bearer-token', 'private-api-key')
        ->and($entries->first()->content['response'])->toBe(['healthy' => true]);
});

it('excludes raw secret API bodies from the actual response watcher', function (): void {
    (new ClientRequestWatcher())->register(app());
    Http::fake(['https://cluster.example/api/v1/namespaces/tenant/secrets/app' => Http::response(['data' => ['APP_KEY' => base64_encode('private-application-key')]])]);
    Http::withToken('private-token')->get('https://cluster.example/api/v1/namespaces/tenant/secrets/app');
    expect(Telescope::$entriesQueue)->toBe([]);
});

it('keeps ordinary non-client entries available', function (): void {
    Telescope::recordLog(IncomingEntry::make(['level' => 'info', 'message' => 'Provisioning complete']));
    expect(Telescope::$entriesQueue)->toHaveCount(1);
});

it('does not install recording hooks when Telescope is disabled', function (): void {
    Telescope::$filterUsing = [];
    config(['telescope.enabled' => false]);
    (new HostingTelemetryServiceProvider(app()))->boot();
    expect(Telescope::$filterUsing)->toBe([]);
});
