<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Containers\Models\ContainerImage;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerImageResource\Pages\CreateContainerImage;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerImageResource\Pages\EditContainerImage;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerImageResource\Pages\ListContainerImages;

final class ContainerImageResource extends Resource
{
    protected static ?string $model = ContainerImage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Containers';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('repository')->required()->maxLength(255),
            TextInput::make('tag')->required()->maxLength(120),
            TextInput::make('digest')->maxLength(255),
            TextInput::make('size_bytes')->numeric()->minValue(0),
            TextInput::make('architecture')->maxLength(80),
            TextInput::make('status')->required()->maxLength(40),
            KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('repository')->searchable(), TextColumn::make('tag'), TextColumn::make('digest'), TextColumn::make('size_bytes')->numeric(), TextColumn::make('status')->badge()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListContainerImages::route('/'), 'create' => CreateContainerImage::route('/create'), 'edit' => EditContainerImage::route('/{record}/edit')];
    }
}
