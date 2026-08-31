<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Dns\Actions\ArchiveZone;
use Liberu\ControlPanel\Dns\Actions\CheckDnsPropagation;
use Liberu\ControlPanel\Dns\Actions\CheckDnsResolution;
use Liberu\ControlPanel\Dns\Actions\SuspendZone;
use Liberu\ControlPanel\Dns\Models\Zone;
use Liberu\ControlPanel\DnsFilament\Resources\ZoneResource\Pages\CreateZone;
use Liberu\ControlPanel\DnsFilament\Resources\ZoneResource\Pages\EditZone;
use Liberu\ControlPanel\DnsFilament\Resources\ZoneResource\Pages\ListZones;

final class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'DNS';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain')->required()->maxLength(253),
            TextInput::make('provider')->required()->maxLength(120),
            Toggle::make('dnssec_enabled')->default(false),
            KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain')->searchable()->sortable(), TextColumn::make('provider'), TextColumn::make('status')->badge(), TextColumn::make('dnssec_enabled')->boolean(), TextColumn::make('created_at')->dateTime()->sortable()])->recordActions([
            Action::make('suspend')
                ->requiresConfirmation()
                ->visible(fn (Zone $record): bool => $record->status->value !== 'suspended' && $record->status->value !== 'archived')
                ->action(fn (Zone $record): Zone => app(SuspendZone::class)->execute($record)),
            Action::make('archive')
                ->requiresConfirmation()
                ->visible(fn (Zone $record): bool => $record->status->value !== 'archived')
                ->action(fn (Zone $record): Zone => app(ArchiveZone::class)->execute($record)),
            Action::make('resolution-check')
                ->requiresConfirmation()
                ->action(function (Zone $record): void {
                    abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
                    abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);
                    app(CheckDnsResolution::class)->execute(['team_id' => auth()->user()->current_team_id, 'zone_id' => $record->getKey()]);
                }),
            Action::make('propagation-check')
                ->requiresConfirmation()
                ->action(function (Zone $record): void {
                    abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
                    abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);
                    app(CheckDnsPropagation::class)->execute(['team_id' => auth()->user()->current_team_id, 'zone_id' => $record->getKey()]);
                }),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListZones::route('/'), 'create' => CreateZone::route('/create'), 'edit' => EditZone::route('/{record}/edit')];
    }
}
