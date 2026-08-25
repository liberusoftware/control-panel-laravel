<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Models\PhpConfiguration;
use Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource\Pages\CreatePhpConfiguration;
use Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource\Pages\EditPhpConfiguration;
use Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource\Pages\ListPhpConfigurations;

final class PhpConfigurationResource extends Resource
{
    protected static ?string $model = PhpConfiguration::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain_id')->required()->uuid(),
            TextInput::make('php_version')->required()->maxLength(20),
            TextInput::make('memory_limit')->numeric()->minValue(1),
            TextInput::make('upload_max_filesize')->numeric()->minValue(1),
            TextInput::make('post_max_size')->numeric()->minValue(1),
            TextInput::make('max_execution_time')->numeric()->minValue(1),
            Toggle::make('display_errors')->default(false),
            Toggle::make('short_open_tag')->default(false),
            KeyValue::make('custom_settings'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('domain.hostname')->searchable(), TextColumn::make('php_version')->badge(),
            TextColumn::make('memory_limit')->suffix(' MB'), TextColumn::make('upload_max_filesize')->suffix(' MB'),
            TextColumn::make('display_errors')->badge(), TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListPhpConfigurations::route('/'), 'create' => CreatePhpConfiguration::route('/create'), 'edit' => EditPhpConfiguration::route('/{record}/edit')];
    }
}
