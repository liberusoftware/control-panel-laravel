<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Facades\Http;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;

final class CheckApplicationHealth
{
    public function __construct(private readonly RecordApplicationMetric $record) {}

    /** @return array<string, mixed> */
    public function execute(HostedApplication $application): array
    {
        $scheme = (bool) ($application->config['ssl_enabled'] ?? true) ? 'https' : 'http';
        $url = $scheme.'://'.$application->domain()->value('hostname');
        $startedAt = microtime(true);

        try {
            $response = Http::timeout(10)->connectTimeout(3)->withHeaders(['User-Agent' => 'Liberu-Control-Panel-Health-Check'])->get($url);
            $statusCode = $response->status();
            $healthy = $response->successful() || $response->redirect();
            $metric = $this->record->execute($application, ['status_code' => $statusCode, 'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000), 'healthy' => $healthy, 'details' => ['url' => $url]]);

            return ['healthy' => $healthy, 'status_code' => $statusCode, 'response_time_ms' => $metric->response_time_ms, 'metric_id' => $metric->getKey()];
        } catch (\Throwable $exception) {
            $metric = $this->record->execute($application, ['response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000), 'healthy' => false, 'details' => ['url' => $url, 'error' => $exception::class]]);

            return ['healthy' => false, 'status_code' => 0, 'response_time_ms' => $metric->response_time_ms, 'metric_id' => $metric->getKey()];
        }
    }
}
