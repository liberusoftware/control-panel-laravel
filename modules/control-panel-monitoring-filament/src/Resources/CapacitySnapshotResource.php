<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Monitoring\Models\CapacitySnapshot;
use Liberu\ControlPanel\MonitoringFilament\Resources\CapacitySnapshotResource\Pages\ListCapacitySnapshots;

final class CapacitySnapshotResource extends MonitoringAssetResource
{
    protected static ?string $model = CapacitySnapshot::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('resource')->searchable(),
            TextColumn::make('used')->numeric(),
            TextColumn::make('available')->numeric(),
            TextColumn::make('unit'),
            TextColumn::make('captured_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCapacitySnapshots::route('/')];
    }
}
