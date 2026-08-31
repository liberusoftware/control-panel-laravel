<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Models\SslCertificate;
use Liberu\ControlPanel\WebHostingFilament\Resources\SslCertificateResource\Pages\CreateSslCertificate;
use Liberu\ControlPanel\WebHostingFilament\Resources\SslCertificateResource\Pages\EditSslCertificate;
use Liberu\ControlPanel\WebHostingFilament\Resources\SslCertificateResource\Pages\ListSslCertificates;

final class SslCertificateResource extends Resource
{
    protected static ?string $model = SslCertificate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain_id')->required()->uuid(), TextInput::make('issuer')->required()->maxLength(120), TextInput::make('serial')->maxLength(255), TextInput::make('status')->required()->maxLength(40), Toggle::make('auto_renew')->default(true), KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain.hostname')->searchable(), TextColumn::make('issuer'), TextColumn::make('status')->badge(), TextColumn::make('expires_at')->dateTime()->sortable(), TextColumn::make('auto_renew')->badge()])->defaultSort('expires_at');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListSslCertificates::route('/'), 'create' => CreateSslCertificate::route('/create'), 'edit' => EditSslCertificate::route('/{record}/edit')];
    }
}
