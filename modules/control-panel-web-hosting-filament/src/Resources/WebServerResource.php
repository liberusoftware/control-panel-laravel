<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Models\WebServer;
use Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource\Pages\CreateWebServer;
use Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource\Pages\EditWebServer;
use Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource\Pages\ListWebServers;

final class WebServerResource extends Resource
{
    protected static ?string $model = WebServer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('node_id')->required()->maxLength(255), TextInput::make('server')->required()->maxLength(80), TextInput::make('version')->maxLength(80), TextInput::make('status')->required()->maxLength(40), KeyValue::make('config'), KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('server')->badge(), TextColumn::make('version'), TextColumn::make('node_id'), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListWebServers::route('/'), 'create' => CreateWebServer::route('/create'), 'edit' => EditWebServer::route('/{record}/edit')];
    }
}
