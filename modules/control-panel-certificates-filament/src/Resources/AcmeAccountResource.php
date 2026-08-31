<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Certificates\Models\AcmeAccount;
use Liberu\ControlPanel\CertificatesFilament\Resources\AcmeAccountResource\Pages\ListAcmeAccounts;

final class AcmeAccountResource extends CertificateLifecycleResource
{
    protected static ?string $model = AcmeAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Certificates';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('email')->searchable(),
            TextColumn::make('directory')->limit(50),
            IconColumn::make('active')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAcmeAccounts::route('/')];
    }
}
