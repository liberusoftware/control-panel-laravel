<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Queries\ListDomains;

final class DomainController
{
    public function index(Request $request, ListDomains $list): JsonResponse
    {
        $domains = $list->execute($request->user()?->current_team_id, $request->integer('per_page', 25));

        return response()->json([
            'data' => $domains->through(static fn (Domain $domain): array => self::resource($domain)),
            'meta' => ['current_page' => $domains->currentPage(), 'per_page' => $domains->perPage(), 'total' => $domains->total()],
        ]);
    }

    public function store(Request $request, CreateDomain $create): JsonResponse
    {
        $data = $request->validate([
            'hostname' => ['required', 'string', 'max:253'],
            'account_id' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $domain = $create->execute(array_merge($data, ['team_id' => $request->user()?->current_team_id]));

        return response()->json(['data' => self::resource($domain)], 201);
    }

    private static function resource(Domain $domain): array
    {
        return ['id' => $domain->getKey(), 'type' => 'control-panel-domain', 'attributes' => $domain->only(['hostname', 'status', 'account_id', 'metadata'])];
    }
}
