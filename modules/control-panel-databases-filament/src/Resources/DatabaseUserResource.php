<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Databases\Models\DatabaseUser;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseUserResource\Pages\ListDatabaseUsers;

final class DatabaseUserResource extends DatabaseFeatureResource
{
    protected static ?string $model = DatabaseUser::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static string|\UnitEnum|null $navigationGroup = 'Databases';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('database_id')->label('Database')->searchable(),
            TextColumn::make('username')->searchable(),
            TextColumn::make('host'),
            IconColumn::make('active')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDatabaseUsers::route('/')];
    }
}
