<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Monitoring\Models\LogEntry;
use Liberu\ControlPanel\MonitoringFilament\Resources\LogEntryResource\Pages\ListLogEntries;

final class LogEntryResource extends MonitoringAssetResource
{
    protected static ?string $model = LogEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('source')->searchable(),
            TextColumn::make('level')->badge(),
            TextColumn::make('message')->limit(100)->searchable(),
            TextColumn::make('logged_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLogEntries::route('/')];
    }
}
