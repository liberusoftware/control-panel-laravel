<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ControlCore\Actions\ExpireNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\RevokeNodeCredential;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource\Pages\CreateNodeCredential;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource\Pages\EditNodeCredential;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource\Pages\ListNodeCredentials;

final class NodeCredentialResource extends Resource
{
    protected static ?string $model = NodeCredential::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('node_id')->options(fn (): array => Node::query()
                ->where('team_id', auth()->user()?->current_team_id)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all())
                ->searchable()
                ->required(),
            TextInput::make('name')->required()->maxLength(160),
            Select::make('type')->options(['ssh' => 'SSH', 'api' => 'API', 'agent' => 'Agent'])->required(),
            TextInput::make('username')->maxLength(160),
            TextInput::make('secret')->password()->revealable()->maxLength(4096),
            TextInput::make('public_key')->maxLength(4096),
            Select::make('status')->options(['active' => 'Active', 'revoked' => 'Revoked', 'expired' => 'Expired'])->required(),
            KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('username'),
            TextColumn::make('status')->badge(),
            TextColumn::make('expires_at')->dateTime()->sortable(),
        ])->recordActions([
            Action::make('revoke')
                ->requiresConfirmation()
                ->visible(fn (NodeCredential $record): bool => $record->status->value !== 'revoked')
                ->action(fn (NodeCredential $record): NodeCredential => app(RevokeNodeCredential::class)->execute($record)),
            Action::make('expire')
                ->requiresConfirmation()
                ->visible(fn (NodeCredential $record): bool => $record->status->value === 'active' && $record->expires_at?->isPast())
                ->action(fn (NodeCredential $record): NodeCredential => app(ExpireNodeCredential::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNodeCredentials::route('/'),
            'create' => CreateNodeCredential::route('/create'),
            'edit' => EditNodeCredential::route('/{record}/edit'),
        ];
    }
}
