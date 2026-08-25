<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Certificates\Actions\RevokeCertificate;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource\Pages\CreateCertificate;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource\Pages\EditCertificate;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource\Pages\ListCertificates;

final class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TagsInput::make('domains')->required()->nestedRecursiveRules(['string', 'max:253']),
            Textarea::make('issuer')->required()->maxLength(160),
            Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'revoked' => 'Revoked', 'expired' => 'Expired'])->required(),
            Textarea::make('certificate_pem')->label('Certificate')->rows(6),
            TextInput::make('private_key')->label('Private key')->password()->revealable()->maxLength(10000),
            KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('domains')->formatStateUsing(fn ($state): string => implode(', ', (array) $state))->searchable(), TextColumn::make('issuer'), TextColumn::make('status')->badge(), TextColumn::make('issued_at')->dateTime(), TextColumn::make('expires_at')->dateTime()->sortable()])
            ->recordActions([
                Action::make('revoke')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record): bool => $record->status->value !== 'revoked')
                    ->action(fn (Certificate $record): Certificate => app(RevokeCertificate::class)->execute($record)),
            ])
            ->defaultSort('expires_at', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListCertificates::route('/'), 'create' => CreateCertificate::route('/create'), 'edit' => EditCertificate::route('/{record}/edit')];
    }
}
