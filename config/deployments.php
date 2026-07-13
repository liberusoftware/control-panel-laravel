<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Git Hosts
    |--------------------------------------------------------------------------
    |
    | Restrict server-side clones so a tenant cannot use the deployment worker
    | to reach arbitrary or internal network hosts.
    |
    */
    'allowed_git_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('ALLOWED_GIT_HOSTS', 'github.com,gitlab.com,bitbucket.org'))
    ))),
];
