<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Security\Models\IntrusionControl;
use Liberu\ControlPanel\SecurityFilament\Resources\IntrusionControlResource\Pages\ListIntrusionControls;

final class IntrusionControlResource extends Resource
{
    protected static ?string $model = IntrusionControl::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-no-symbol';

    protected static string|\UnitEnum|null $navigationGroup = 'Security & Compliance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('kind')->searchable(),
            TextColumn::make('subject_type'),
            TextColumn::make('subject_id')->searchable(),
            TextColumn::make('action')->badge(),
            TextColumn::make('threshold'),
            TextColumn::make('window_seconds'),
            TextColumn::make('enabled')->badge(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListIntrusionControls::route('/')];
    }
}
