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

        $version = self::version($request);

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()]);
        }

        if ($version !== null && $version !== $token->last_plugin_version) {
            $token->forceFill(['last_plugin_version' => $version]);
        }

        $token->save();

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('plugin_token_id', $token->id);
        $request->attributes->set('plugin_version', $version);

        return $next($request);
    }

    /** The X-Plugin-Version header, or null when absent or not shaped like a version. */
    private static function version(Request $request): ?string
    {
        $version = trim((string) $request->header('X-Plugin-Version'));

        return preg_match('/^[0-9A-Za-z.+-]{1,32}$/', $version) === 1 ? $version : null;
    }
}
