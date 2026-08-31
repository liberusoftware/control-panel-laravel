<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Containers\Models\ContainerRegistry;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerRegistryResource\Pages\ListContainerRegistries;

final class ContainerRegistryResource extends ContainerAssetResource
{
    protected static ?string $model = ContainerRegistry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Containers';

    public static function table(Table $table): Table
    {
        return parent::table($table)->columns([
            ...$table->getColumns(),
            IconColumn::make('active')->boolean(),
            IconColumn::make('tls_verify')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContainerRegistries::route('/')];
    }
}
