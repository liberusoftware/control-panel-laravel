<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Mail\Models\DeliveryDiagnostic;
use Liberu\ControlPanel\MailFilament\Resources\DeliveryDiagnosticResource\Pages\ListDeliveryDiagnostics;

final class DeliveryDiagnosticResource extends MailFeatureResource
{
    protected static ?string $model = DeliveryDiagnostic::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string|\UnitEnum|null $navigationGroup = 'Email & Messaging';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('mail_account_id')->label('Mail account')->searchable(),
            TextColumn::make('recipient')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('checked_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDeliveryDiagnostics::route('/')];
    }
}
