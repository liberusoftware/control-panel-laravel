<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Containers\Models\ContainerVolume;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerVolumeResource\Pages\ListContainerVolumes;

final class ContainerVolumeResource extends ContainerAssetResource
{
    protected static ?string $model = ContainerVolume::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Containers';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('driver')->badge(),
            TextColumn::make('mount_path'),
            TextColumn::make('size_bytes')->numeric(),
            TextColumn::make('status')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContainerVolumes::route('/')];
    }
}
