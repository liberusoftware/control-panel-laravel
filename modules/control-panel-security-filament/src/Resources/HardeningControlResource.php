<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Security\Models\HardeningControl;
use Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource\Pages\CreateHardeningControl;
use Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource\Pages\EditHardeningControl;
use Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource\Pages\ListHardeningControls;

final class HardeningControlResource extends Resource
{
    protected static ?string $model = HardeningControl::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('subject_type')->required()->maxLength(255),
            TextInput::make('subject_id')->required()->maxLength(255),
            TextInput::make('control')->required()->maxLength(160),
            Toggle::make('desired')->default(true),
            Toggle::make('observed')->default(false),
            TextInput::make('status')->required()->maxLength(40),
            KeyValue::make('evidence'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('control')->searchable(), TextColumn::make('subject_id'), TextColumn::make('desired')->badge(), TextColumn::make('observed')->badge(), TextColumn::make('status')->badge(), TextColumn::make('checked_at')->dateTime()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListHardeningControls::route('/'), 'create' => CreateHardeningControl::route('/create'), 'edit' => EditHardeningControl::route('/{record}/edit')];
    }
}
