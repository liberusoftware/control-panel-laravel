<?php

declare(strict_types=1);

namespace Liberu\Foundation\ApiAccess\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class IdempotencyStore
{
    public function begin(string $identity, string $key, string $requestBody): ?object
    {
        $hash = hash('sha256', $requestBody);

        return DB::transaction(function () use ($identity, $key, $hash): ?object {
            $existing = DB::table('api_idempotency_keys')
                ->where('identity_ref', $identity)
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if ($existing && Carbon::parse($existing->expires_at)->isFuture()) {
                if (! hash_equals($existing->request_hash, $hash)) {
                    throw new RuntimeException('Idempotency key was reused with a different request.');
                }

                return $existing;
            }

            $now = now();
            $attributes = [
                'identity_ref' => $identity,
                'key' => $key,
                'request_hash' => $hash,
                'response_status' => null,
                'response_body' => null,
                'expires_at' => $now->copy()->addHours((int) config('api-access.idempotency_hours', 24)),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('api_idempotency_keys')->where('id', $existing->id)->update($attributes);

                return null;
            }

            $inserted = DB::table('api_idempotency_keys')->insertOrIgnore($attributes);
            if ($inserted === 1) {
                return null;
            }

            $created = DB::table('api_idempotency_keys')
                ->where('identity_ref', $identity)
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if ($created && ! hash_equals($created->request_hash, $hash)) {
                throw new RuntimeException('Idempotency key was reused with a different request.');
            }

            return $created;
        });
    }

    public function complete(string $identity, string $key, int $status, string $body): void
    {
        DB::table('api_idempotency_keys')
            ->where('identity_ref', $identity)
            ->where('key', $key)
            ->update(['response_status' => $status, 'response_body' => $body, 'updated_at' => now()]);
    }
}
