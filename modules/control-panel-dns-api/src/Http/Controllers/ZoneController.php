<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\Actions\RecordDnsCheck;
use Liberu\ControlPanel\Dns\Actions\RegisterDnsFeature;
use Liberu\ControlPanel\Dns\Models\Zone;
use Liberu\ControlPanel\Dns\Queries\ListZones;

final class ZoneController
{
    public function index(Request $request, ListZones $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $zones = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $zones->through(static fn (Zone $zone): array => self::resource($zone)), 'meta' => ['current_page' => $zones->currentPage(), 'per_page' => $zones->perPage(), 'total' => $zones->total()]]);
    }

    public function store(Request $request, CreateZone $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['domain' => ['required', 'string', 'max:253'], 'provider' => ['nullable', 'string', 'max:100'], 'dnssec_enabled' => ['sometimes', 'boolean'], 'metadata' => ['nullable', 'array']]);
        $zone = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($zone)], 201);
    }

    public function record(Request $request, CreateRecord $create): JsonResponse
    {
        $teamId=$request->user()?->current_team_id; abort_if($teamId===null,403,'A current team is required.');
        $data=$request->validate(['zone_id'=>['required','uuid'],'name'=>['nullable','string','max:253'],'type'=>['required','string'],'content'=>['required','string','max:4096'],'ttl'=>['nullable','integer','min:60'],'priority'=>['nullable','integer','min:0'],'metadata'=>['nullable','array']]);
        $item=$create->execute($data); return response()->json(['data'=>['id'=>$item->getKey(),'type'=>'control-panel-dns-record','attributes'=>$item->only(['zone_id','name','type','content','ttl','priority','metadata'])]],201);
    }

    public function check(Request $request, RecordDnsCheck $record): JsonResponse
    {
        $teamId=$request->user()?->current_team_id; abort_if($teamId===null,403,'A current team is required.');
        $data=$request->validate(['zone_id'=>['nullable','uuid'],'kind'=>['nullable','in:validation,propagation,dnssec'],'status'=>['nullable','in:pending,passed,failed'],'result'=>['nullable','array']]);
        $item=$record->execute(array_merge($data,['team_id'=>$teamId])); return response()->json(['data'=>['id'=>$item->getKey(),'type'=>'control-panel-dns-check','attributes'=>$item->only(['zone_id','kind','status','result','checked_at'])]],201);
    }

    public function feature(Request $request, RegisterDnsFeature $register): JsonResponse
    {
        $teamId=$request->user()?->current_team_id; abort_if($teamId===null,403,'A current team is required.'); $data=$request->validate(['kind'=>['required','in:template,dnssec,provider,validation,propagation'],'payload'=>['required','array']]); $item=$register->execute(array_merge($data['payload'],['kind'=>$data['kind'],'team_id'=>$teamId])); return response()->json(['data'=>['id'=>$item->getKey(),'type'=>'control-panel-dns-'.$data['kind'],'attributes'=>$item->toArray()]],201);
    }

    private static function resource(Zone $zone): array
    {
        return ['id' => $zone->getKey(), 'type' => 'control-panel-dns-zone', 'attributes' => $zone->only(['domain', 'status', 'provider', 'dnssec_enabled', 'metadata'])];
    }
}
