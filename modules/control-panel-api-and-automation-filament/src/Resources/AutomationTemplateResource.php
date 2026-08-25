<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationTemplateResource\Pages\CreateAutomationTemplate;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationTemplateResource\Pages\EditAutomationTemplate;

final class AutomationTemplateResource extends Resource
{
    protected static ?string $model = AutomationTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('version')->required()->maxLength(40),
            TextInput::make('description')->maxLength(1000),
            KeyValue::make('inputs')->label('Inputs'),
            KeyValue::make('steps')->label('Steps'),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('version'), TextColumn::make('active')->badge(), TextColumn::make('created_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAutomationTemplates::route('/'), 'create' => CreateAutomationTemplate::route('/create'), 'edit' => EditAutomationTemplate::route('/{record}/edit')];
    }
}
