<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Containers\Models\ContainerLifecycle;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerLifecycleResource\Pages\ListContainerLifecycles;

final class ContainerLifecycleResource extends ContainerAssetResource
{
    protected static ?string $model = ContainerLifecycle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Containers';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('workload_id')->label('Workload')->searchable(),
            TextColumn::make('operation')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('requested_at')->dateTime()->sortable(),
            TextColumn::make('completed_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContainerLifecycles::route('/')];
    }
}
