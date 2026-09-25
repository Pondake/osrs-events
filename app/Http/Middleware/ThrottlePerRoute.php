<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * `throttle:N,M` with one counter per route instead of one per visitor.
 *
 * The framework keys an unnamed limit on the user or address alone, so every
 * such limit on the site shares a bucket and the strictest number wins: three
 * wrong passwords used up the requests a reset link is allowed.
 */
class ThrottlePerRoute extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();

        return parent::resolveRequestSignature($request)
            .'|'.implode(',', $route?->methods() ?? []).'|'.$route?->uri();
    }
}
