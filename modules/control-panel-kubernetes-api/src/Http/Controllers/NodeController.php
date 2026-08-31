<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Kubernetes\Actions\CordonNode;
use Liberu\ControlPanel\Kubernetes\Actions\DrainNode;
use Liberu\ControlPanel\Kubernetes\Actions\LabelNode;
use Liberu\ControlPanel\Kubernetes\Actions\UncordonNode;
use Liberu\ControlPanel\Kubernetes\Actions\UnlabelNode;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;

final class NodeController
{
    public function cordon(Request $request, string $node, CordonNode $cordon): JsonResponse
    {
        return response()->json(['data' => self::resource($cordon->execute($this->findForTeam($request, $node)))]);
    }

    public function uncordon(Request $request, string $node, UncordonNode $uncordon): JsonResponse
    {
        return response()->json(['data' => self::resource($uncordon->execute($this->findForTeam($request, $node)))]);
    }

    public function drain(Request $request, string $node, DrainNode $drain): JsonResponse
    {
        $data = $request->validate([
            'force' => ['sometimes', 'boolean'],
            'grace_period' => ['sometimes', 'integer', 'min:0'],
            'timeout' => ['sometimes', 'string', 'max:32'],
        ]);

        return response()->json(['data' => self::resource($drain->execute($this->findForTeam($request, $node), $data))]);
    }

    public function label(Request $request, string $node, LabelNode $label): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:253'], 'value' => ['required', 'string', 'max:63']]);

        return response()->json(['data' => self::resource($label->execute($this->findForTeam($request, $node), $data['key'], $data['value']))]);
    }

    public function unlabel(Request $request, string $node, UnlabelNode $unlabel): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:253']]);

        return response()->json(['data' => self::resource($unlabel->execute($this->findForTeam($request, $node), $data['key']))]);
    }

    private function findForTeam(Request $request, string $id): KubernetesNode
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return KubernetesNode::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private static function resource(KubernetesNode $node): array
    {
        return [
            'id' => $node->getKey(),
            'type' => 'control-panel-kubernetes-node',
            'attributes' => $node->only(['cluster_id', 'name', 'kubernetes_version', 'container_runtime', 'os_image', 'kernel_version', 'architecture', 'status', 'status_message', 'schedulable', 'labels', 'taints', 'capacity', 'allocatable', 'last_heartbeat_at']),
        ];
    }
}
