<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Databases\Models\DatabaseRemoteAccess;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseRemoteAccessResource\Pages\ListDatabaseRemoteAccess;

final class DatabaseRemoteAccessResource extends DatabaseFeatureResource
{
    protected static ?string $model = DatabaseRemoteAccess::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Databases';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('database_id')->label('Database')->searchable(),
            TextColumn::make('source_cidr')->searchable(),
            TextColumn::make('port')->numeric(),
            IconColumn::make('tls_required')->boolean(),
            IconColumn::make('active')->boolean(),
            TextColumn::make('expires_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDatabaseRemoteAccess::route('/')];
    }
}
