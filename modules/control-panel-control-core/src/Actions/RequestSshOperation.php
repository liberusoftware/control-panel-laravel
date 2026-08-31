<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final readonly class RequestSshOperation
{
    public function __construct(private CreateOperationTask $createTask) {}

    /** @param array<string, mixed> $payload */
    public function execute(string $teamId, string $nodeId, string $operation, string $idempotencyKey, array $payload = []): OperationTask
    {
        if (! in_array($operation, ['ssh.deploy-public-key', 'ssh.test-connection'], true)) {
            throw ValidationException::withMessages(['operation' => 'The SSH operation is not supported.']);
        }

        if ($operation === 'ssh.deploy-public-key') {
            $username = trim((string) ($payload['username'] ?? ''));
            $publicKey = trim((string) ($payload['public_key'] ?? ''));
            if (! preg_match('/^[a-z_][a-z0-9_-]{0,31}$/i', $username)) {
                throw ValidationException::withMessages(['username' => 'A valid SSH username is required.']);
            }
            if (! preg_match('/^(ssh-(rsa|ed25519)|ecdsa-sha2-nistp\d+)\s+\S+/', $publicKey)) {
                throw ValidationException::withMessages(['public_key' => 'A valid OpenSSH public key is required.']);
            }
            $payload = ['username' => $username, 'public_key' => $publicKey];
        } else {
            $payload = [];
        }

        return $this->createTask->execute([
            'team_id' => $teamId,
            'node_id' => $nodeId,
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
        ]);
    }
}
