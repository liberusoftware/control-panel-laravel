<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Monitoring\Models\UptimeCheck;
use Liberu\ControlPanel\MonitoringFilament\Resources\UptimeCheckResource\Pages\ListUptimeChecks;

final class UptimeCheckResource extends MonitoringAssetResource
{
    protected static ?string $model = UptimeCheck::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('monitor_id')->label('Monitor')->searchable(),
            TextColumn::make('endpoint')->limit(80)->searchable(),
            TextColumn::make('status_code')->numeric(),
            TextColumn::make('response_time_ms')->label('Response (ms)')->numeric(),
            IconColumn::make('healthy')->boolean(),
            TextColumn::make('checked_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListUptimeChecks::route('/')];
    }
}
