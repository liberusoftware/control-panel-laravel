<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationSchedule;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationScheduleResource\Pages\CreateAutomationSchedule;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationScheduleResource\Pages\EditAutomationSchedule;

final class AutomationScheduleResource extends Resource
{
    protected static ?string $model = AutomationSchedule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Automation & Integrations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('cron')->required()->maxLength(120),
            TextInput::make('timezone')->required()->maxLength(80)->default('UTC'),
            TextInput::make('template_id')->required()->uuid(),
            Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'paused' => 'Paused', 'failed' => 'Failed'])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('cron'), TextColumn::make('timezone'), TextColumn::make('status')->badge(), TextColumn::make('next_run_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAutomationSchedules::route('/'), 'create' => CreateAutomationSchedule::route('/create'), 'edit' => EditAutomationSchedule::route('/{record}/edit')];
    }
}
