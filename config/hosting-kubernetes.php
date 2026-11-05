<?php

return [
    // Sources bind operator-approved images to team-owned cluster/workload records.
    // No arbitrary PodSpec, host paths, service account grants or shell commands are accepted.
    'sources' => [],
    // 'site' => ['team_id' => 'team-id', 'cluster_id' => 'cluster-uuid', 'workload_id' => 'workload-uuid',
    //     'connection' => 'production', 'settings' => ['allowed_images' => ['registry.example/site@sha256:...'],
    //     'runtime_class' => 'gvisor', 'network_policy_enforced' => true, 'max_replicas' => 3, 'port' => 8080]],
    // Database sources use the same team/cluster binding plus database_id and validated engine-specific settings.
    'data_sources' => [],
    'connections' => [
        'production' => [
            'url' => env('HOSTING_KUBERNETES_URL'),
            'token' => env('HOSTING_KUBERNETES_TOKEN'),
            'ca_certificate' => env('HOSTING_KUBERNETES_CA', ''),
        ],
    ],
];
