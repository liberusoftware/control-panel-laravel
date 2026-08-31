<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Accounts\Actions\RevokeDelegation;
use Liberu\ControlPanel\Accounts\Models\AccountDelegation;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource\Pages\CreateAccountDelegation;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource\Pages\EditAccountDelegation;

final class AccountDelegationResource extends Resource
{
    protected static ?string $model = AccountDelegation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-minus';

    protected static string|\UnitEnum|null $navigationGroup = 'Accounts & Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('account_id')->relationship(
                'account',
                'name',
                modifyQueryUsing: fn (Builder $query): Builder => $query->where('team_id', auth()->user()?->current_team_id),
            )->required()->searchable()->preload(),
            TextInput::make('delegate_id')->required()->maxLength(255),
            KeyValue::make('permissions')->label('Delegated permissions'),
            DateTimePicker::make('expires_at')->nullable()->native(false),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('delegate_id')->searchable(),
            TextColumn::make('account_id'),
            TextColumn::make('active')->badge(),
            TextColumn::make('expires_at')->dateTime(),
        ])->recordActions([
            Action::make('revoke')
                ->requiresConfirmation()
                ->visible(fn (AccountDelegation $record): bool => $record->active)
                ->action(fn (AccountDelegation $record): AccountDelegation => app(RevokeDelegation::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountDelegations::route('/'),
            'create' => CreateAccountDelegation::route('/create'),
            'edit' => EditAccountDelegation::route('/{record}/edit'),
        ];
    }
}
