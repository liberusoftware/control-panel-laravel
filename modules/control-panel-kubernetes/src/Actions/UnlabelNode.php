<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;

final class UnlabelNode
{
    public function execute(KubernetesNode $node, string $key): KubernetesNode
    {
        $key = trim($key);
        if ($key === '' || mb_strlen($key) > 253) {
            throw ValidationException::withMessages(['key' => 'A label key of no more than 253 characters is required.']);
        }

        return DB::transaction(function () use ($node, $key): KubernetesNode {
            $locked = KubernetesNode::query()->whereKey($node->getKey())->lockForUpdate()->firstOrFail();
            if (! array_key_exists($key, $locked->labels ?? [])) {
                throw ValidationException::withMessages(['key' => 'The node does not have this label.']);
            }
            $labels = $locked->labels ?? [];
            unset($labels[$key]);
            $locked->forceFill(['labels' => $labels])->save();

            return $locked->refresh();
        });
    }
}
