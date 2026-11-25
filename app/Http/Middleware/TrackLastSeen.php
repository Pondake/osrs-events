<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subMinute()))) {
            $now = now();
            // Through the base query builder so updated_at is left alone.
            User::whereKey($user->getKey())->toBase()->update(['last_seen_at' => $now]);
            $user->last_seen_at = $now;
            $user->syncOriginalAttribute('last_seen_at');
        }

        return $next($request);
    }
}
