<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Databases\Models\DatabasePrivilege;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabasePrivilegeResource\Pages\ListDatabasePrivileges;

final class DatabasePrivilegeResource extends DatabaseFeatureResource
{
    protected static ?string $model = DatabasePrivilege::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Databases';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('database_id')->label('Database')->searchable(),
            TextColumn::make('database_user_id')->label('User')->searchable(),
            TextColumn::make('privilege')->badge(),
            TextColumn::make('object_name')->searchable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDatabasePrivileges::route('/')];
    }
}
