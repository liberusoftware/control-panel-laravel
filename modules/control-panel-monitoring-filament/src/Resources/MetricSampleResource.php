<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Monitoring\Models\MetricSample;
use Liberu\ControlPanel\MonitoringFilament\Resources\MetricSampleResource\Pages\ListMetricSamples;

final class MetricSampleResource extends MonitoringAssetResource
{
    protected static ?string $model = MetricSample::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('monitor_id')->label('Monitor')->searchable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('value')->numeric(),
            TextColumn::make('unit'),
            TextColumn::make('sampled_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMetricSamples::route('/')];
    }
}
