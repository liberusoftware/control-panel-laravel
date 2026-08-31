<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Containers\Models\ContainerLimit;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerLimitResource\Pages\ListContainerLimits;

final class ContainerLimitResource extends ContainerAssetResource
{
    protected static ?string $model = ContainerLimit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Containers';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('workload_id')->label('Workload')->searchable(),
            TextColumn::make('cpu_millis')->label('CPU (milli)')->numeric(),
            TextColumn::make('memory_bytes')->label('Memory (bytes)')->numeric(),
            TextColumn::make('pids')->numeric(),
            TextColumn::make('restart_policy')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContainerLimits::route('/')];
    }
}
