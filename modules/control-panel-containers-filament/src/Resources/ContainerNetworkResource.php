<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Containers\Models\ContainerNetwork;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerNetworkResource\Pages\ListContainerNetworks;

final class ContainerNetworkResource extends ContainerAssetResource
{
    protected static ?string $model = ContainerNetwork::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-share';

    protected static string|\UnitEnum|null $navigationGroup = 'Containers';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('driver')->badge(),
            TextColumn::make('subnet'),
            TextColumn::make('gateway'),
            TextColumn::make('status')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContainerNetworks::route('/')];
    }
}
