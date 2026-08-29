<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAccount;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\MailFilament\Resources\MailAccountResource\Pages\CreateMailAccount;
use Liberu\ControlPanel\MailFilament\Resources\MailAccountResource\Pages\EditMailAccount;
use Liberu\ControlPanel\MailFilament\Resources\MailAccountResource\Pages\ListMailAccounts;

final class MailAccountResource extends Resource
{
    protected static ?string $model = MailAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain')->required()->maxLength(253),
            TextInput::make('address')->required()->email()->maxLength(320),
            TextInput::make('quota_bytes')->numeric()->minValue(0),
            TextInput::make('status')->required()->maxLength(40),
            KeyValue::make('settings'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('address')->searchable()->sortable(), TextColumn::make('domain')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('quota_bytes')->numeric(), TextColumn::make('created_at')->dateTime()])->recordActions([
            DeleteAction::make()->action(fn (MailAccount $record): void => app(DeleteMailAccount::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMailAccounts::route('/'), 'create' => CreateMailAccount::route('/create'), 'edit' => EditMailAccount::route('/{record}/edit')];
    }
}
