<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Models\RuntimeVersion;
use Liberu\ControlPanel\WebHostingFilament\Resources\RuntimeVersionResource\Pages\CreateRuntimeVersion;
use Liberu\ControlPanel\WebHostingFilament\Resources\RuntimeVersionResource\Pages\EditRuntimeVersion;
use Liberu\ControlPanel\WebHostingFilament\Resources\RuntimeVersionResource\Pages\ListRuntimeVersions;

final class RuntimeVersionResource extends Resource
{
    protected static ?string $model = RuntimeVersion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket';

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
           TextInput::make('runtime')->required()->maxLength(40), TextInput::make('version')->required()->maxLength(40), Toggle::make('available')->default(true), Toggle::make('default')->default(false),
]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('runtime')->badge(), TextColumn::make('version')->searchable(), TextColumn::make('available')->badge(), TextColumn::make('default')->badge()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListRuntimeVersions::route('/'), 'create' => CreateRuntimeVersion::route('/create'), 'edit' => EditRuntimeVersion::route('/{record}/edit')];
    }
}
