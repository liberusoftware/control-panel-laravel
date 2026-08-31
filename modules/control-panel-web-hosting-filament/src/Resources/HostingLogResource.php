<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Models\HostingLog;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource\Pages\CreateHostingLog;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource\Pages\EditHostingLog;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource\Pages\ListHostingLogs;

final class HostingLogResource extends Resource
{
    protected static ?string $model = HostingLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain_id')->required()->uuid(),
            TextInput::make('kind')->required()->maxLength(80),
            TextInput::make('level')->required()->maxLength(40),
            Textarea::make('message')->required()->maxLength(10000),
            KeyValue::make('context'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain_id'), TextColumn::make('kind')->badge(), TextColumn::make('level')->badge(), TextColumn::make('message')->limit(80), TextColumn::make('occurred_at')->dateTime()->sortable()])->defaultSort('occurred_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListHostingLogs::route('/'), 'create' => CreateHostingLog::route('/create'), 'edit' => EditHostingLog::route('/{record}/edit')];
    }
}
