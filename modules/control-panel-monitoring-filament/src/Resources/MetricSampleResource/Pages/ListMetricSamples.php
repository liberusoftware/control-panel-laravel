<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MetricSampleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\MetricSampleResource;

final class ListMetricSamples extends ListRecords
{
    protected static string $resource = MetricSampleResource::class;
}
