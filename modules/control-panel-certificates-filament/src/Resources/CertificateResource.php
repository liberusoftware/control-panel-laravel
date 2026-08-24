<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource\Pages\ListCertificates;

final class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domains')->formatStateUsing(fn ($state): string => implode(', ', (array) $state))->searchable(), TextColumn::make('issuer'), TextColumn::make('status')->badge(), TextColumn::make('issued_at')->dateTime(), TextColumn::make('expires_at')->dateTime()->sortable()])->defaultSort('expires_at', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListCertificates::route('/')];
    }
}
