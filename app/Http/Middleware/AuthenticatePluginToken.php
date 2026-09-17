<?php

namespace App\Http\Middleware;

use App\Models\PluginToken;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** `Authorization: Bearer ose_…` for the RuneLite plugin API. The API does not exist while the plugin is off. */
class AuthenticatePluginToken
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(Setting::get('runelite_plugin_mode') === 'off', 404);

        $token = PluginToken::findByPlain((string) $request->bearerToken());
        $user = $token?->user;

        if ($user === null) {
            return response()->json(['message' => trans('plugin.api_unauthenticated')], 401);
        }

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('plugin_token_id', $token->id);

        return $next($request);
    }
}
