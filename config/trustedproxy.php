<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | The load balancers or reverse proxies in front of the app, as a comma
    | separated list of IPs / CIDR ranges, or "*" to trust the calling proxy.
    | Leave it empty when clients connect directly: trusting forwarded headers
    | from anyone would let them spoof their IP and dodge the rate limits on
    | login and registration. Read by Laravel's TrustProxies middleware.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
