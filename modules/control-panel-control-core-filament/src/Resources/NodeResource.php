<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ControlCore\Actions\DecommissionNode;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource\Pages\CreateNode;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource\Pages\EditNode;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource\Pages\ListNodes;

final class NodeResource extends Resource
{
    protected static ?string $model = Node::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('hostname')->required()->maxLength(255),
            TextInput::make('platform')->required()->maxLength(120),
            TextInput::make('agent_version')->maxLength(80),
            KeyValue::make('capabilities')->label('Declared capabilities'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('hostname')->searchable(),
            TextColumn::make('platform'),
            TextColumn::make('status')->badge(),
            TextColumn::make('last_seen_at')->dateTime()->sortable(),
        ])->recordActions([
            Action::make('decommission')
                ->requiresConfirmation()
                ->visible(fn (Node $record): bool => $record->status->value !== 'decommissioned')
                ->action(fn (Node $record): Node => app(DecommissionNode::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListNodes::route('/'), 'create' => CreateNode::route('/create'), 'edit' => EditNode::route('/{record}/edit')];
    }
}
