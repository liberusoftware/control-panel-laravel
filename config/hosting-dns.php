<?php

return [
    // Operator-owned aliases bind an inventory zone, team and domain to one cluster.
    // The executor owns all non-SOA records in these zones. Never alias unmanaged zones.
    'sources' => [
        'primary' => [
            'team_id' => env('HOSTING_DNS_TEAM_ID'),
            'zone_id' => env('HOSTING_DNS_ZONE_ID'),
            'domain' => env('HOSTING_DNS_DOMAIN'),
            'cluster' => 'powerdns',
        ],
    ],
    'clusters' => [
        'powerdns' => [
            'ns1' => ['url' => env('HOSTING_DNS_NS1_URL'), 'api_key' => env('HOSTING_DNS_NS1_KEY')],
            'ns2' => ['url' => env('HOSTING_DNS_NS2_URL'), 'api_key' => env('HOSTING_DNS_NS2_KEY')],
        ],
    ],
];
