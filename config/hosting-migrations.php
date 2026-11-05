<?php

return [
    // Operator-owned bindings only. Keep administrator credentials outside source control and image layers.
    // Initial support inspects Virtualmin 8 GPL/Pro inventory; this does not perform a full migration.
    'staging_root' => env('HOSTING_MIGRATION_STAGING_ROOT'),
    'max_collection_bytes' => 1073741824, // Bound total plaintext staging; increase only with a reviewed capacity budget.
    'sources' => [],
    // 'legacy' => ['provider' => 'virtualmin8', 'team_id' => 'team-id', 'cluster_id' => 'cluster-uuid',
    //     'url' => 'https://legacy.example.com:10000', 'credentials_file' => '/protected/legacy-credentials.json',
    //     'ca_certificate' => '/protected/source-ca.pem', 'domains' => ['example.com']],
    // Instead of credentials_file, bind node_id + credential_id for an active API NodeCredential.
    // Its metadata must contain provider=virtualmin8 and origin matching the approved HTTPS URL.
    // Native backup collection additionally needs transport: host, username, port (integer),
    // identity_file, known_hosts_file; both files must be protected and host keys independently verified.
    // It accepts all four provider labels for byte collection, without claiming format conversion/import.
    // Use the single '*' domain entry only when this team owns and approves the entire source server.
];
