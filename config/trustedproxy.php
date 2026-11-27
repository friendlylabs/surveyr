<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Behind a reverse proxy or load balancer (nginx in front, Cloudflare, a
    | container ingress) every request arrives from the proxy, so a response's
    | IP address, sign-in throttling and HTTPS detection all see the proxy. List
    | the proxies here and their X-Forwarded-* headers are believed: the
    | addresses, comma separated (CIDR ranges work), or * to trust whichever
    | machine connects (only when nothing can reach the app but the proxy).
    | Empty trusts none, so a visitor can't fake their address with a header.
    | Laravel Cloud, Forge and Vapor hosts are trusted on their own when empty.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
