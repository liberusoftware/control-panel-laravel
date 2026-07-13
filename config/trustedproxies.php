<?php

return [
    /*
    | Trust no forwarding proxy by default. Set a comma-separated list of
    | proxy IP addresses or CIDR ranges, or explicitly use * only in a network
    | where direct client access to the application is impossible.
    */
    'proxies' => env('TRUSTED_PROXIES'),
];
