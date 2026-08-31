<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Mail\Models\MailControl;
use Liberu\ControlPanel\MailFilament\Resources\MailControlResource\Pages\ListMailControls;

final class MailControlResource extends MailFeatureResource
{
    protected static ?string $model = MailControl::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Email & Messaging';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('mail_account_id')->label('Mail account')->searchable(),
            IconColumn::make('spam_filter_enabled')->boolean(),
            TextColumn::make('spam_threshold')->numeric(),
            IconColumn::make('virus_scan_enabled')->boolean(),
            IconColumn::make('autoresponder_enabled')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMailControls::route('/')];
    }
}
