<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;

final class LabelNode
{
    public function execute(KubernetesNode $node, string $key, string $value): KubernetesNode
    {
        $key = trim($key);
        $value = trim($value);

        if ($key === '' || mb_strlen($key) > 253) {
            throw ValidationException::withMessages(['key' => 'A label key of no more than 253 characters is required.']);
        }

        if ($value === '' || mb_strlen($value) > 63) {
            throw ValidationException::withMessages(['value' => 'A label value of no more than 63 characters is required.']);
        }

        return DB::transaction(function () use ($node, $key, $value): KubernetesNode {
            $locked = KubernetesNode::query()->whereKey($node->getKey())->lockForUpdate()->firstOrFail();
            $labels = $locked->labels ?? [];
            $labels[$key] = $value;
            $locked->forceFill(['labels' => $labels])->save();

            return $locked->refresh();
        });
    }
}
