<?php

namespace App\Http\Middleware;

use App\Models\Event;
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
        $user = $request->user()?->load('roles:id,name');
        $roles = $user?->roles->pluck('name') ?? collect();

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
                // One-off payload from one action to one page, exactly like
                // lastRoll above: the admin diagnostics sweep returns command
                // output rather than a sentence, and a toast is the wrong
                // shape for twenty lines of it.
                'sweepOutput' => fn () => $request->session()->get('sweepOutput'),
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
                // Shared rather than passed per page: the create-event modal
                // opens from the events index, the admin list and onboarding.
                'eventTypes' => collect(Event::EVENT_TYPES)
                    ->map(fn ($meta, $key) => ['value' => $key, ...$meta])
                    ->values()
                    ->all(),
                // Keyed by metric kind so the create form can offer the right
                // list once a type is picked, without a round trip.
                'metricsByKind' => [
                    'skill' => Event::SKILL_METRICS,
                    'boss' => Event::BOSS_METRICS,
                ],
                // Withheld while the pre-launch lock is on and this visitor
                // has not passed it. An announcement is written for the
                // people already using the site ("summer bingo starts
                // Friday, sign up in #events") and the lock screen is the
                // one page a stranger can reach — so the banner was the one
                // thing leaking past a door built to leak nothing.
                //
                // Withheld rather than hidden client-side: a prop that
                // reaches the browser has already been disclosed, whatever
                // the template does with it afterwards.
                // Whether the pre-launch door is shut for THIS visitor. The
                // lock screen already drops the site chrome; the login page
                // has to as well, or "let me in" hands a stranger the full
                // nav bar and a banner one click later.
                'locked' => fn () => Setting::get('site_lock_enabled') && ! $this->announcementVisible($request),
                'announcement' => fn () => $this->announcementVisible($request) ? Setting::get('announcement') : null,
                'announcementType' => Setting::get('announcement_type'),
                'defaultBoardSize' => Setting::get('default_board_size'),
                'defaultDiceRollLimit' => Setting::get('default_dice_roll_limit'),
                'defaultEventDuration' => Setting::get('default_event_duration'),
                // Whether the event settings form offers a Discord webhook
                // field at all. Shared for the same reason the board defaults
                // are: that form opens from three different places.
                'discordWebhooksEnabled' => (bool) Setting::get('discord_webhooks_enabled'),
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
                    'osrsUsername' => $user->osrs_username,
                    // Drives the recurring "we can't find this account"
                    // notice, which lives in the layout for the same reason
                    // the onboarding modal does — it has to follow the user
                    // rather than belong to one page. Null covers both never
                    // checked and checked-and-missing; neither is confirmed,
                    // and the app treats them identically.
                    'osrsVerified' => $user->osrs_verified_at !== null,
                    // Boolean, not the address itself: the modal only needs
                    // to know whether account recovery is possible, and the
                    // email is already exposed where it's actually shown
                    // (Settings\AccountController).
                    'hasEmail' => $user->email !== null,
                    // Read on every page by AppRoot, before the silent
                    // opt-in runs. Push is unusual in needing a shared prop
                    // at all: the browser's own state says permission is
                    // granted and a subscription exists, which is
                    // indistinguishable from "wants notifications" unless
                    // the server's explicit off switch travels with it.
                    // Without this, turning notifications off would undo
                    // itself on the next page load.
                    'pushOptedOut' => $user->push_opted_out_at !== null,
                ] : null,
            ],
        ];
    }

    /**
     * Whether this request is allowed to see the site-wide announcement.
     *
     * Everything is visible on an unlocked site; on a locked one it takes
     * the same pass the rest of the site takes — the shared password or an
     * admin session. See EnsureSiteUnlocked.
     */
    private function announcementVisible(Request $request): bool
    {
        if (! Setting::get('site_lock_enabled')) {
            return true;
        }

        return $request->user()?->isAdmin()
            || $request->session()->get(EnsureSiteUnlocked::SESSION_KEY) === true;
    }
}
