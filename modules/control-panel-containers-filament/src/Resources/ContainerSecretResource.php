<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Containers\Models\ContainerSecret;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerSecretResource\Pages\ListContainerSecrets;

final class ContainerSecretResource extends ContainerAssetResource
{
    protected static ?string $model = ContainerSecret::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Containers';

    public static function table(Table $table): Table
    {
        return $table->columns([
            ...$table->getColumns(),
            IconColumn::make('active')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContainerSecrets::route('/')];
    }
}
