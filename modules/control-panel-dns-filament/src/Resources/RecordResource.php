<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Dns\Actions\DeleteRecord;
use Liberu\ControlPanel\Dns\Actions\ValidateRecord;
use Liberu\ControlPanel\Dns\Models\Record;
use Liberu\ControlPanel\DnsFilament\Resources\RecordResource\Pages\CreateRecord;
use Liberu\ControlPanel\DnsFilament\Resources\RecordResource\Pages\EditRecord;
use Liberu\ControlPanel\DnsFilament\Resources\RecordResource\Pages\ListRecords;

final class RecordResource extends Resource
{
    protected static ?string $model = Record::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'DNS';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('zone_id')->required()->uuid(), TextInput::make('name')->required()->maxLength(253),
            TextInput::make('type')->required()->in(['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA']),
            TextInput::make('content')->required()->maxLength(4096), TextInput::make('ttl')->required()->integer()->minValue(60)->maxValue(86400)->default(3600),
            TextInput::make('priority')->integer()->minValue(0)->maxValue(65535),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('zone.domain')->label('Zone')->sortable(), TextColumn::make('name')->searchable(), TextColumn::make('type')->badge(), TextColumn::make('content')->searchable(), TextColumn::make('ttl')->sortable()])->recordActions([
            Action::make('validate')->action(fn (Record $record): array => app(ValidateRecord::class)->execute(['type' => $record->type, 'name' => $record->name, 'content' => $record->content])),
            DeleteAction::make()->action(fn (Record $record) => app(DeleteRecord::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('zone', fn (Builder $query) => $query->where('team_id', auth()->user()?->current_team_id));
    }

    public static function getPages(): array
    {
        return ['index' => ListRecords::route('/'), 'create' => CreateRecord::route('/create'), 'edit' => EditRecord::route('/{record}/edit')];
    }
}
