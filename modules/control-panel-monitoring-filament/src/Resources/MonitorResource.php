<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Monitoring\Models\Monitor;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource\Pages\ListMonitors;

final class MonitorResource extends Resource
{
    protected static ?string $model = Monitor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('subject_type'), TextColumn::make('status')->badge(), TextColumn::make('last_checked_at')->dateTime()->sortable(), TextColumn::make('created_at')->dateTime()])->defaultSort('last_checked_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMonitors::route('/')];
    }
}
