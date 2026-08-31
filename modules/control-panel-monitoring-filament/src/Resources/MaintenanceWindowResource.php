<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Monitoring\Actions\CancelMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Actions\DeleteMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Models\MaintenanceWindow;
use Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource\Pages\CreateMaintenanceWindow;
use Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource\Pages\EditMaintenanceWindow;
use Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource\Pages\ListMaintenanceWindows;

final class MaintenanceWindowResource extends Resource
{
    protected static ?string $model = MaintenanceWindow::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), DateTimePicker::make('starts_at')->required(), DateTimePicker::make('ends_at')->required(), TextInput::make('scope')->required()->maxLength(255), KeyValue::make('details')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('scope'), TextColumn::make('starts_at')->dateTime()->sortable(), TextColumn::make('ends_at')->dateTime(), TextColumn::make('status')->badge()])->recordActions([
            Action::make('cancel')->requiresConfirmation()->visible(fn (MaintenanceWindow $record): bool => ! in_array($record->status, ['cancelled', 'completed'], true))->action(fn (MaintenanceWindow $record): MaintenanceWindow => app(CancelMaintenanceWindow::class)->execute($record)),
            DeleteAction::make()->visible(fn (MaintenanceWindow $record): bool => $record->team_id === auth()->user()?->current_team_id && ! in_array($record->status, ['active', 'completed'], true))->action(fn (MaintenanceWindow $record) => app(DeleteMaintenanceWindow::class)->execute($record)),
        ])->defaultSort('starts_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMaintenanceWindows::route('/'), 'create' => CreateMaintenanceWindow::route('/create'), 'edit' => EditMaintenanceWindow::route('/{record}/edit')];
    }
}
