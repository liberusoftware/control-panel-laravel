<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Mail\Models\MailOperation;
use Liberu\ControlPanel\MailFilament\Resources\MailOperationResource\Pages\ListMailOperations;

final class MailOperationResource extends MailFeatureResource
{
    protected static ?string $model = MailOperation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Email & Messaging';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('mail_account_id')->label('Mail account')->searchable(),
            TextColumn::make('operation')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMailOperations::route('/')];
    }
}
