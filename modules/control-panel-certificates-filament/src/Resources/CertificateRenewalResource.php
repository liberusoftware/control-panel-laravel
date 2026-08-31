<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Certificates\Models\CertificateRenewal;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateRenewalResource\Pages\ListCertificateRenewals;

final class CertificateRenewalResource extends CertificateLifecycleResource
{
    protected static ?string $model = CertificateRenewal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Certificates';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('certificate_id')->label('Certificate')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('attempts')->numeric(),
            TextColumn::make('scheduled_at')->dateTime()->sortable(),
            TextColumn::make('completed_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCertificateRenewals::route('/')];
    }
}
