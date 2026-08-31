<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Actions\DeleteVirtualHost;
use Liberu\ControlPanel\WebHosting\Models\VirtualHost;
use Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource\Pages\CreateVirtualHost;
use Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource\Pages\EditVirtualHost;
use Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource\Pages\ListVirtualHosts;

final class VirtualHostResource extends Resource
{
    protected static ?string $model = VirtualHost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain_id')->required()->uuid(), TextInput::make('node_id')->maxLength(255), TextInput::make('server')->required()->maxLength(80), TextInput::make('runtime')->maxLength(80), TextInput::make('document_root')->required()->maxLength(2048), Toggle::make('active')->default(true), KeyValue::make('desired_state'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain.hostname')->searchable(), TextColumn::make('server')->badge(), TextColumn::make('runtime'), TextColumn::make('document_root'), TextColumn::make('active')->badge()])->recordActions([
            DeleteAction::make()->action(fn (VirtualHost $record) => app(DeleteVirtualHost::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('domain', fn (Builder $query) => $query->where('team_id', auth()->user()?->current_team_id));
    }

    public static function getPages(): array
    {
        return ['index' => ListVirtualHosts::route('/'), 'create' => CreateVirtualHost::route('/create'), 'edit' => EditVirtualHost::route('/{record}/edit')];
    }
}
