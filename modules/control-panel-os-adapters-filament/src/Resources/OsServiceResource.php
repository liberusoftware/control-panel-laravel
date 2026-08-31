<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\OsAdapters\Models\OsService;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsServiceResource\Pages\CreateOsService;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsServiceResource\Pages\EditOsService;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsServiceResource\Pages\ListOsServices;

final class OsServiceResource extends Resource
{
    protected static ?string $model = OsService::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Server Management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('node_id')->required()->maxLength(255),
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('version')->maxLength(80),
            TextInput::make('status')->required()->maxLength(40),
            Toggle::make('enabled')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('version'), TextColumn::make('status')->badge(), TextColumn::make('enabled')->badge(), TextColumn::make('node_id')]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListOsServices::route('/'), 'create' => CreateOsService::route('/create'), 'edit' => EditOsService::route('/{record}/edit')];
    }
}
