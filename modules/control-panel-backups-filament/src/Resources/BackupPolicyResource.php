<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource\Pages\ListBackupPolicies;

final class BackupPolicyResource extends Resource
{
    protected static ?string $model = BackupPolicy::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';
    public static function form(Schema $schema): Schema { return $schema->components([]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('storage_driver'), TextColumn::make('retention_days'), TextColumn::make('encrypted')->badge(), TextColumn::make('active')->badge()]); }
    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id); }
    public static function getPages(): array { return ['index' => ListBackupPolicies::route('/')]; }
}
