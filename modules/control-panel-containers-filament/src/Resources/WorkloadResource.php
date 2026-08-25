<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Containers\Models\Workload;
use Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource\Pages\CreateWorkload;
use Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource\Pages\EditWorkload;
use Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource\Pages\ListWorkloads;

final class WorkloadResource extends Resource
{
    protected static ?string $model = Workload::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('node_id')->maxLength(255),
            TextInput::make('image')->required()->maxLength(512),
            TextInput::make('status')->required()->maxLength(40),
            KeyValue::make('specification')->label('Workload specification'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('image')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('node_id'), TextColumn::make('created_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListWorkloads::route('/'), 'create' => CreateWorkload::route('/create'), 'edit' => EditWorkload::route('/{record}/edit')];
    }
}
