<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationDefinition;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource\Pages\CreateAutomationDefinition;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource\Pages\EditAutomationDefinition;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource\Pages\ListAutomationDefinitions;

final class AutomationDefinitionResource extends Resource
{
    protected static ?string $model = AutomationDefinition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|\UnitEnum|null $navigationGroup = 'Automation & Integrations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('kind')->required()->maxLength(80),
            Select::make('status')->options(['draft' => 'Draft', 'active' => 'Active', 'disabled' => 'Disabled'])->required(),
            TextInput::make('schedule')->maxLength(120),
            KeyValue::make('definition')->label('Definition'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('kind'), TextColumn::make('status')->badge(), TextColumn::make('schedule'), TextColumn::make('created_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListAutomationDefinitions::route('/'), 'create' => CreateAutomationDefinition::route('/create'), 'edit' => EditAutomationDefinition::route('/{record}/edit')];
    }
}
