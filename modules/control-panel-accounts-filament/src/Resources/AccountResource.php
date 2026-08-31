<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Accounts\Actions\ActivateAccount;
use Liberu\ControlPanel\Accounts\Actions\ArchiveAccount;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountResource\Pages\CreateAccount;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountResource\Pages\EditAccount;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountResource\Pages\ListAccounts;

final class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Accounts & Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('owner_id')->label('Owner')->required()->maxLength(255),
            Select::make('type')->options([
                'administrator' => 'Administrator',
                'reseller' => 'Reseller',
                'customer' => 'Customer',
            ])->required(),
            Select::make('parent_id')->label('Parent account')->nullable()->searchable()->preload()
                ->relationship('parent', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('team_id', auth()->user()?->current_team_id))
                ->getOptionLabelFromRecordUsing(fn (Account $record): string => $record->name),
            KeyValue::make('quota_overrides')->label('Quota limits')->keyLabel('Resource')->valueLabel('Limit'),
            KeyValue::make('brand')->label('Branding')->keyLabel('Property')->valueLabel('Value'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('owner_id')->label('Owner')->searchable(),
            TextColumn::make('quota_overrides')->label('Quota limits')->formatStateUsing(static fn (?array $state): string => (string) count($state ?? [])),
            TextColumn::make('brand')->label('Branding')->formatStateUsing(static fn (?array $state): string => (string) count($state ?? [])),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->recordActions([
            Action::make('suspend')
                ->form([Textarea::make('reason')->required()->maxLength(1000)])
                ->visible(fn (Account $record): bool => $record->status->value === 'active')
                ->action(fn (Account $record, array $data): Account => app(SuspendAccount::class)->execute($record, $data['reason'])),
            Action::make('activate')
                ->visible(fn (Account $record): bool => $record->status->value === 'suspended')
                ->action(fn (Account $record): Account => app(ActivateAccount::class)->execute($record)),
            Action::make('archive')
                ->requiresConfirmation()
                ->visible(fn (Account $record): bool => $record->status->value !== 'archived')
                ->action(fn (Account $record): Account => app(ArchiveAccount::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}
