<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Dns\Models\DnsCheck;
use Liberu\ControlPanel\DnsFilament\Resources\DnsCheckResource\Pages\CreateDnsCheck;
use Liberu\ControlPanel\DnsFilament\Resources\DnsCheckResource\Pages\EditDnsCheck;
use Liberu\ControlPanel\DnsFilament\Resources\DnsCheckResource\Pages\ListDnsChecks;

final class DnsCheckResource extends Resource
{
    protected static ?string $model = DnsCheck::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'DNS';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('zone_id')->required()->uuid(),
            TextInput::make('kind')->required()->maxLength(80),
            TextInput::make('status')->required()->maxLength(40),
            KeyValue::make('result'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('kind')->badge(), TextColumn::make('status')->badge(), TextColumn::make('checked_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListDnsChecks::route('/'), 'create' => CreateDnsCheck::route('/create'), 'edit' => EditDnsCheck::route('/{record}/edit')];
    }
}
