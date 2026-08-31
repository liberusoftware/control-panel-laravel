<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Monitoring\Models\StatusSnapshot;
use Liberu\ControlPanel\MonitoringFilament\Resources\StatusSnapshotResource\Pages\ListStatusSnapshots;

final class StatusSnapshotResource extends MonitoringAssetResource
{
    protected static ?string $model = StatusSnapshot::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('component')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('message')->limit(100),
            TextColumn::make('checked_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListStatusSnapshots::route('/')];
    }
}
