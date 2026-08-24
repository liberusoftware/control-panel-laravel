<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\Models\Zone;
use Liberu\ControlPanel\Dns\Queries\ListZones;

final class ZoneController
{
    public function index(Request $request, ListZones $list): JsonResponse
    {
        $zones = $list->execute($request->user()?->current_team_id, $request->integer('per_page', 25));

        return response()->json(['data' => $zones->through(static fn (Zone $zone): array => self::resource($zone)), 'meta' => ['current_page' => $zones->currentPage(), 'per_page' => $zones->perPage(), 'total' => $zones->total()]]);
    }

    public function store(Request $request, CreateZone $create): JsonResponse
    {
        $data = $request->validate(['domain' => ['required', 'string', 'max:253'], 'provider' => ['nullable', 'string', 'max:100'], 'dnssec_enabled' => ['sometimes', 'boolean'], 'metadata' => ['nullable', 'array']]);
        $zone = $create->execute(array_merge($data, ['team_id' => $request->user()?->current_team_id]));

        return response()->json(['data' => self::resource($zone)], 201);
    }

    private static function resource(Zone $zone): array
    {
        return ['id' => $zone->getKey(), 'type' => 'control-panel-dns-zone', 'attributes' => $zone->only(['domain', 'status', 'provider', 'dnssec_enabled', 'metadata'])];
    }
}
