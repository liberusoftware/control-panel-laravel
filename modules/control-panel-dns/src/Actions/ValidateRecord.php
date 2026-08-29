<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Validation\ValidationException;

final class ValidateRecord
{
    /** @return array{valid: true, message: string} */
    public function execute(array $attributes): array
    {
        $type = strtoupper(trim((string) ($attributes['record_type'] ?? $attributes['type'] ?? '')));
        $name = trim((string) ($attributes['name'] ?? ''));
        $value = trim((string) ($attributes['value'] ?? $attributes['content'] ?? ''));

        if (! in_array($type, ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'PTR', 'SRV', 'CAA'], true)) {
            throw ValidationException::withMessages(['record_type' => 'Unsupported DNS record type.']);
        }
        if (! preg_match('/^(@|[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)$/', $name)) {
            throw ValidationException::withMessages(['name' => 'The DNS record name is invalid.']);
        }
        if ($value === '' || mb_strlen($value) > 1000) {
            throw ValidationException::withMessages(['value' => 'A DNS record value is required.']);
        }

        $valid = match ($type) {
            'A' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            'AAAA' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            'CNAME', 'MX', 'NS', 'PTR' => (bool) preg_match('/^(?=.{1,253}\\.?$)([a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\\.)*[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\\.?$/', $value),
            'SRV' => (bool) preg_match('/^(?:[0-9]|[1-9][0-9]{1,4})\\s+(?:[0-9]|[1-9][0-9]{1,4})\\s+(?:[0-9]|[1-9][0-9]{1,4})\\s+\\S+$/', $value),
            'CAA' => (bool) preg_match('/^(?:0|[1-9][0-9]*)\\s+[a-zA-Z0-9-]+\\s+".*"$/', $value),
            'TXT' => true,
        };

        if (! $valid) {
            throw ValidationException::withMessages(['value' => "The value is invalid for {$type} records."]);
        }

        return ['valid' => true, 'message' => 'DNS record is valid.'];
    }
}
