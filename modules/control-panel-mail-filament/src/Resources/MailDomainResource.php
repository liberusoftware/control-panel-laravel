<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Mail\Models\MailDomain;
use Liberu\ControlPanel\MailFilament\Resources\MailDomainResource\Pages\CreateMailDomain;
use Liberu\ControlPanel\MailFilament\Resources\MailDomainResource\Pages\EditMailDomain;
use Liberu\ControlPanel\MailFilament\Resources\MailDomainResource\Pages\ListMailDomains;

final class MailDomainResource extends Resource
{
    protected static ?string $model = MailDomain::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('domain')->required()->maxLength(253), TextInput::make('status')->required()->maxLength(40), KeyValue::make('dkim'), KeyValue::make('spf'), KeyValue::make('dmarc')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain')->searchable()->sortable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()->sortable()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMailDomains::route('/'), 'create' => CreateMailDomain::route('/create'), 'edit' => EditMailDomain::route('/{record}/edit')];
    }
}
