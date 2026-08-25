<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Monitoring\Actions\ResolveMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Models\MonitoringEvent;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource\Pages\CreateMonitoringEvent;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource\Pages\EditMonitoringEvent;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource\Pages\ListMonitoringEvents;

final class MonitoringEventResource extends Resource
{
    protected static ?string $model = MonitoringEvent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('monitor_id')->required()->uuid(),
            TextInput::make('kind')->required()->maxLength(80),
            TextInput::make('status')->disabled()->dehydrated(false),
            KeyValue::make('payload'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('kind')->badge(), TextColumn::make('status')->badge(), TextColumn::make('starts_at')->dateTime(), TextColumn::make('ends_at')->dateTime()])->recordActions([
            Action::make('resolve')->requiresConfirmation()->visible(fn (MonitoringEvent $record): bool => $record->kind === 'incident' && $record->status === 'open')->action(fn (MonitoringEvent $record): MonitoringEvent => app(ResolveMonitoringEvent::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMonitoringEvents::route('/'), 'create' => CreateMonitoringEvent::route('/create'), 'edit' => EditMonitoringEvent::route('/{record}/edit')];
    }
}
use Filament\Actions\Action;
