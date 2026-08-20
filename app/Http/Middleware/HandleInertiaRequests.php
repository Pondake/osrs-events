<?php

namespace App\Http\Middleware;

use App\Models\Setting;
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
        $user = $request->user()?->load('userRoles.role');
        $roles = $user?->userRoles->pluck('role.name') ?? collect();

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
                // Raw roll value for DiceRoller.vue to pick a die face —
                // kept separate from boardSave's already-formatted sentence
                // rather than parsing a number back out of display text.
                'lastRoll' => fn () => $request->session()->get('last-roll'),
            ],
            // Shared globally because two of these are needed off any page:
            // the announcement renders in AppRoot's layout, and the board
            // defaults seed the create-board form wherever it's opened from
            // (the boards index, the admin list, the onboarding modal).
            // Setting::cached() is a single cache read, not a query.
            'site' => fn () => [
                // Shared rather than fetched per page: the footer needs it
                // everywhere, including logged-out pages with no controller
                // of their own, and this way footer and page content cannot
                // drift to different URLs.
                'kofiUrl' => Setting::get('kofi_url'),
                'announcement' => Setting::get('announcement'),
                'announcementType' => Setting::get('announcement_type'),
                'defaultBoardSize' => Setting::get('default_board_size'),
                'defaultDiceRollLimit' => Setting::get('default_dice_roll_limit'),
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
                    // Raw role list — needed for AppHeader.vue's nav (isEditor/
                    // isTeamManager gating, matching the old AppHeader.vue) and
                    // Profile.vue's badges. Not collapsed into more isX flags
                    // here since the roles a nav might care about can grow
                    // without a matching HandleInertiaRequests change every time.
                    'roles' => $roles,
                    // Drives OnboardingModal.vue's auto-open in AppRoot —
                    // shared globally because the modal lives in the layout,
                    // not on any one page that could pass it as a prop.
                    'needsOnboarding' => $user->onboarding_completed_at === null,
                    // Boolean, not the address itself: the modal only needs
                    // to know whether account recovery is possible, and the
                    // email is already exposed where it's actually shown
                    // (Settings\AccountController).
                    'hasEmail' => $user->email !== null,
                ] : null,
            ],
        ];
    }
}
