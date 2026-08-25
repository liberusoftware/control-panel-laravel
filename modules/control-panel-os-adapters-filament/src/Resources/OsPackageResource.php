<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\OsAdapters\Models\OsPackage;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource\Pages\CreateOsPackage;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource\Pages\EditOsPackage;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource\Pages\ListOsPackages;

final class OsPackageResource extends Resource
{
    protected static ?string $model = OsPackage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('node_id')->required()->maxLength(255),
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('version')->required()->maxLength(80),
            TextInput::make('architecture')->maxLength(80),
            TextInput::make('status')->required()->maxLength(40),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('version'), TextColumn::make('architecture'), TextColumn::make('status')->badge(), TextColumn::make('node_id')]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListOsPackages::route('/'), 'create' => CreateOsPackage::route('/create'), 'edit' => EditOsPackage::route('/{record}/edit')];
    }
}
