<?php

return [
    // Operator-only cluster configuration for already installed Stalwart nodes.
    // Every node must share the same TLS PostgreSQL datastore; local blob/search stores are rejected.
    // nodes: ['mail1' => ['url' => 'https://mail1.example', 'token' => env('MAIL1_MANAGEMENT_TOKEN')], ...]
    // data_store: ['@type' => 'PostgreSql', 'host' => 'postgres.example', 'port' => 5432,
    //     'database' => 'mail', 'authUsername' => 'mail', 'useTls' => true, 'allowInvalidCerts' => false]
    // memory_store: ['@type' => 'RedisCluster', 'urls' => ['rediss://cache1.example:6379' => true, ...],
    //     'allowInvalidCerts' => false, 'authUsername' => 'default',
    //     'authSecret' => ['@type' => 'EnvironmentVariable', 'variableName' => 'STALWART_REDIS_PASSWORD']]
    'sources' => [],
];
