<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Mail\Actions\ConfigureMailControls;
use Liberu\ControlPanel\Mail\Actions\CreateMailAccount;
use Liberu\ControlPanel\Mail\Actions\CreateMailAlias;
use Liberu\ControlPanel\Mail\Actions\RecordDeliveryDiagnostic;
use Liberu\ControlPanel\Mail\Actions\RecordMailOperation;
use Liberu\ControlPanel\Mail\Actions\RegisterMailDomain;
use Liberu\ControlPanel\Mail\Actions\RotateDkimKey;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\Mail\Models\MailDomain;
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

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = MailAccount::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-mail-account', 'attributes' => $item->toArray()]]);
    }

    public function store(Request $request, CreateMailAccount $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['domain' => ['required', 'string', 'max:255'], 'address' => ['required', 'email:rfc', 'max:255'], 'quota_bytes' => ['nullable', 'integer', 'min:0']]);
        $item = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    public function operation(Request $request, RecordMailOperation $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['mail_account_id' => ['nullable', 'uuid'], 'operation' => ['required', 'in:deliver,quarantine,spam-check,dkim-rotate'], 'status' => ['nullable', 'in:queued,running,completed,failed'], 'details' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-mail-operation', 'attributes' => $item->only(['mail_account_id', 'operation', 'status', 'details'])]], 201);
    }

    public function alias(Request $request, CreateMailAlias $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['domain' => ['required', 'string', 'max:255'], 'address' => ['required', 'string', 'max:255'], 'destinations' => ['required', 'array', 'min:1'], 'destinations.*' => ['email']]);
        $item = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-mail-alias', 'attributes' => $item->only(['domain', 'address', 'destinations', 'active'])]], 201);
    }

    public function controls(Request $request, ConfigureMailControls $configure): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['mail_account_id' => ['required', 'uuid'], 'spam_filter_enabled' => ['sometimes', 'boolean'], 'spam_threshold' => ['nullable', 'integer', 'min:1', 'max:30'], 'spam_action' => ['nullable', 'in:tag,quarantine,reject'], 'virus_scan_enabled' => ['sometimes', 'boolean'], 'autoresponder_enabled' => ['sometimes', 'boolean'], 'autoresponder_subject' => ['nullable', 'string', 'max:255'], 'autoresponder_message' => ['nullable', 'string', 'max:10000'], 'keep_copy_on_server' => ['sometimes', 'boolean']]);
        $item = $configure->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-mail-control', 'attributes' => $item->only(['mail_account_id', 'spam_filter_enabled', 'spam_threshold', 'spam_action', 'virus_scan_enabled', 'autoresponder_enabled', 'keep_copy_on_server'])]], 201);
    }

    public function diagnostic(Request $request, RecordDeliveryDiagnostic $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['mail_account_id' => ['nullable', 'uuid'], 'message_id' => ['nullable', 'string', 'max:255'], 'recipient' => ['required', 'email'], 'status' => ['nullable', 'string', 'max:50'], 'response' => ['nullable', 'string', 'max:10000']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-mail-delivery-diagnostic', 'attributes' => $item->only(['mail_account_id', 'message_id', 'recipient', 'status', 'response', 'checked_at'])]], 201);
    }

    public function rotateDkim(Request $request, RotateDkimKey $rotate): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['domain' => ['required', 'string', 'max:253'], 'selector' => ['nullable', 'string', 'max:63']]);
        $key = $rotate->execute($teamId, $data['domain'], $data['selector'] ?? 'default');
        $publicKey = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n", "\r"], '', $key->public_key);

        return response()->json(['data' => [
            'id' => $key->getKey(),
            'type' => 'control-panel-mail-dkim-key',
            'attributes' => $key->only(['domain', 'selector', 'active', 'rotated_at']) + ['dns_record' => 'v=DKIM1; k=rsa; p='.$publicKey],
        ]], 201);
    }

    public function domain(Request $request, RegisterMailDomain $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['domain' => ['required', 'string', 'max:253'], 'dkim' => ['nullable', 'array'], 'spf' => ['nullable', 'array'], 'dmarc' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => (string) $teamId]));

        return response()->json(['data' => self::domainResource($item)], 201);
    }

    private static function resource(MailAccount $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-mail-account', 'attributes' => $item->only(['domain', 'address', 'status', 'quota_bytes'])];
    }

    private static function domainResource(MailDomain $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-mail-domain', 'attributes' => $item->only(['domain', 'status', 'dkim', 'spf', 'dmarc'])];
    }
}
