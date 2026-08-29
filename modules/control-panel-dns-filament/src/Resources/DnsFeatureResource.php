<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class DnsFeatureResource extends Resource
{
    /** @return list<string> */
    abstract protected static function featureFields(): array;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(array_map(
            static fn (string $field): TextInput => TextInput::make($field)->disabled(),
            static::featureFields(),
        ));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array_map(
                static fn (string $field): TextColumn => TextColumn::make($field)->label(str_replace('_', ' ', ucfirst($field))),
                static::featureFields(),
            ))
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $teamId = auth()->user()?->current_team_id;
        $query = parent::getEloquentQuery();

        return $teamId === null
            ? $query->whereRaw('1 = 0')
            : $query->where('team_id', $teamId);
    }
}
