<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Shared globally so every page can gate UI without its own fetch —
     * replaces the old frontend's useAuthStore() + usePermissions()
     * (stale/frontend/app/stores/auth.ts,
     * stale/frontend/app/composables/usePermissions.ts). isAdmin/canCreateBoards
     * mirror usePermissions.ts's own computed flags exactly, just resolved
     * server-side instead of over a GraphQL round-trip after mount.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            // Ziggy's route() helper needs this explicitly shared as an
            // Inertia prop for SSR — the @routes Blade directive
            // (resources/views/app.blade.php) only writes a <script> tag
            // for the BROWSER's window.Ziggy global. The Node SSR process
            // never sees that script; without this share, calling route()
            // from any SSR-rendered page throws "Cannot read properties of
            // undefined (reading 'login')" deep inside ziggy-js, since
            // ssr.js passes `page.props.ziggy` into the ZiggyVue plugin.
            // Confirmed by curling /osrs-snakes-and-ladders: its "Start a
            // board" button's :href="route('login')" call crashed the
            // entire page's SSR render, not just that one element.
            'ziggy' => fn () => (new Ziggy)->toArray(),
            'flash' => [
                'boardSave' => fn () => $request->session()->get('board-save'),
                'boardSaveError' => fn () => $request->session()->get('board-save-error'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'discordUsername' => $user->discord_username,
                    'nickname' => $user->nickname,
                    'avatarUrl' => $user->avatar_url,
                    'isAdmin' => $user->isAdmin(),
                    'canCreateBoards' => $user->hasPermission('canCreateBoards'),
                    'canCreateTiles' => $user->hasPermission('canCreateTiles'),
                ] : null,
            ],
        ];
    }
}
