<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Security\Models\SecurityFinding;
use Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource\Pages\ListSecurityFindings;

final class SecurityFindingResource extends Resource
{
    protected static ?string $model = SecurityFinding::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('summary')->searchable(), TextColumn::make('severity')->badge(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()->sortable()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListSecurityFindings::route('/')];
    }
}
