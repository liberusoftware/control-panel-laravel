<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\ControlCore\Queries\ListAuditEntries;

final class AuditController
{
    public function index(Request $request, ListAuditEntries $entries): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $page = $entries->execute($teamId, $request->integer('per_page', 25));

        return response()->json([
            'data' => $page->through(static fn ($entry): array => [
                'id' => $entry->getKey(),
                'type' => 'control-panel-audit-entry',
                'attributes' => $entry->only(['event', 'subject_type', 'subject_id', 'created_at']),
            ]),
            'meta' => ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total()],
        ]);
    }
}
