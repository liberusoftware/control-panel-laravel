<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Models\PhpConfiguration;
use Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource\Pages\ListPhpConfigurations;

final class PhpConfigurationResource extends Resource
{
    protected static ?string $model = PhpConfiguration::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema { return $schema->components([]); }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('domain.hostname')->searchable(), TextColumn::make('php_version')->badge(),
            TextColumn::make('memory_limit')->suffix(' MB'), TextColumn::make('upload_max_filesize')->suffix(' MB'),
            TextColumn::make('display_errors')->badge(), TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id); }

    public static function getPages(): array { return ['index' => ListPhpConfigurations::route('/')]; }
}
