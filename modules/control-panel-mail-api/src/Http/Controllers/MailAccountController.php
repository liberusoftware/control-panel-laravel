<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Mail\Actions\CreateMailAccount;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\Mail\Queries\ListMailAccounts;

final class MailAccountController
{
    public function index(Request $request, ListMailAccounts $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (MailAccount $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateMailAccount $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['domain' => ['required', 'string', 'max:255'], 'address' => ['required', 'email:rfc', 'max:255'], 'quota_bytes' => ['nullable', 'integer', 'min:0']]);
        $item = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    private static function resource(MailAccount $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-mail-account', 'attributes' => $item->only(['domain', 'address', 'status', 'quota_bytes'])];
    }
}
