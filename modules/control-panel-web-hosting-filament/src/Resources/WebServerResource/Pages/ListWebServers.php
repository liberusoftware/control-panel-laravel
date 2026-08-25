<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource\Pages;
use Filament\Resources\Pages\ListRecords; use Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource;
final class ListWebServers extends ListRecords { protected static string $resource = WebServerResource::class; }
