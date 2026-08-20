<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BoardController as AdminBoardController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\InviteController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OsrsUsernameController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardInviteController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlayerBoardController;
use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\Settings\ProfileController as SettingsProfileController;
use App\Http\Controllers\SkillRaceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TileController;
use App\Http\Controllers\UserSearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Throttled per-IP — these are unauthenticated by definition, so nothing
// else gates how often they can be hit. `redirect` just builds a URL to
// Discord (cheap, but still a public entry point); `callback` does the
// actual OAuth token exchange + DB writes (new user/guild sync), the more
// expensive and more sensitive of the two, so it gets the tighter limit.
Route::get('/auth/discord/redirect', [DiscordController::class, 'redirect'])
    ->middleware('throttle:20,1')
    ->name('login');
Route::get('/auth/discord/callback', [DiscordController::class, 'callback'])
    ->middleware('throttle:10,1')
    ->name('auth.discord.callback');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    // Discard the authenticated session entirely on logout, not just the
    // auth guard's own state — same session-hygiene reasoning as the
    // regenerate() calls on login (DiscordController, Auth\*Controller).
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

// Email/password path — see routes 26-31 above for the Discord OAuth path,
// which stays the primary flow. `guest` keeps an already-authenticated user
// from landing back on a signup/login form. Route names are `auth.*` rather
// than Laravel's usual bare `register`/`login` because `login` is already
// taken by the Discord redirect route above (route('login') is used
// throughout the app for the Discord login button).
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('auth.register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('auth.register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('auth.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('auth.login.store');

    // Password reset by emailed link. The POST is throttled hardest of the
    // lot: it's the one that actually sends mail, so it's the one worth
    // abusing to spam a third party's inbox.
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');
    // Name MUST stay `password.reset` — Laravel's built-in ResetPassword
    // notification builds its link with route('password.reset', ...), so
    // renaming this silently breaks the emailed URL.
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

Route::get('/', [LandingController::class, 'home'])->name('home');
Route::get('/osrs-snakes-and-ladders', [LandingController::class, 'snakesAndLadders'])
    ->name('landing.snakes');
Route::get('/osrs-clan-events', [LandingController::class, 'clanEvents'])->name('landing.clan-events');
Route::get('/osrs-event-ideas', [LandingController::class, 'eventIdeas'])->name('landing.event-ideas');

Route::get('/privacy', fn () => Inertia::render('Privacy'))->name('privacy');
Route::get('/terms', fn () => Inertia::render('Terms'))->name('terms');

// Snakes & Ladders was the whole product, so the app said "boards"
// everywhere. It is becoming one event type among several (ROADMAP phase 5),
// so the public vocabulary is events now. The model is still Board — see the
// backlog for why that rename is sequenced separately.
Route::get('/events', [BoardController::class, 'index'])->name('events.index');
Route::get('/events/all', [BoardController::class, 'all'])->name('events.all');
Route::get('/my-events', [BoardController::class, 'mine'])
    ->middleware(['auth', 'require-osrs-username'])
    ->name('events.mine');
Route::get('/events/{event}', [BoardController::class, 'show'])
    ->middleware(['auth', 'require-osrs-username'])
    ->name('events.show');
Route::get('/events/{event}/leaderboard', [LeaderboardController::class, 'show'])
    ->middleware(['auth', 'require-osrs-username'])
    ->name('events.leaderboard');

// Server-sent events, not a normal endpoint: it holds a PHP worker open for
// ~45 seconds per connected viewer. See SkillRaceController for why SSE over
// WebSockets and what that costs to run.
Route::get('/events/{event}/standings/stream', [SkillRaceController::class, 'stream'])
    ->middleware(['auth', 'require-osrs-username'])
    ->name('events.standings.stream');
Route::get('/events/{event}/join/{token}', [BoardController::class, 'joinByLink'])->name('events.join-link');

// The one thing a logged-in account is allowed to do before it has an OSRS
// username — everything else in the group below redirects here until it does.
// See RequireOsrsUsername for why Discord logins can't be asked any earlier.
Route::middleware('auth')->group(function () {
    Route::get('/welcome/osrs-username', [OsrsUsernameController::class, 'create'])->name('osrs.create');
    Route::post('/welcome/osrs-username', [OsrsUsernameController::class, 'store'])->name('osrs.store');
});

Route::middleware(['auth', 'require-osrs-username'])->group(function () {
    Route::post('/events', [BoardController::class, 'store'])->name('events.store');
    Route::patch('/events/{event}', [BoardController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [BoardController::class, 'destroy'])->name('events.destroy');

    Route::post('/events/{event}/roll', [PlayerBoardController::class, 'roll'])->name('events.roll');
    Route::post('/events/{event}/tiles/{tile}/toggle', [PlayerBoardController::class, 'toggleTile'])->name('events.tiles.toggle');
    Route::post('/events/{event}/join', [BoardController::class, 'join'])->name('events.join');

    // Entering a race is a separate decision from being allowed to look at
    // one — see SkillRaceController::enter.
    Route::post('/events/{event}/enter', [SkillRaceController::class, 'enter'])->name('events.enter');
    Route::delete('/events/{event}/enter', [SkillRaceController::class, 'leave'])->name('events.leave');

    Route::get('/events/{event}/invites', [BoardInviteController::class, 'index'])->name('events.invites.index');
    Route::post('/events/{event}/invites', [BoardInviteController::class, 'store'])->name('events.invites.store');
    Route::delete('/events/{event}/invites/{invite}', [BoardInviteController::class, 'destroy'])->name('events.invites.destroy');

    Route::post('/events/{event}/tiles', [TileController::class, 'upsert'])->name('events.tiles.upsert');
    Route::delete('/events/{event}/tiles/{tile}', [TileController::class, 'destroy'])->name('events.tiles.destroy');

    Route::get('/events/{event}/teams', [BoardController::class, 'teamsIndex'])->name('events.teams.index');
    Route::post('/events/{event}/teams', [BoardController::class, 'addTeam'])->name('events.teams.add');
    Route::delete('/events/{event}/teams/{team}', [BoardController::class, 'removeTeam'])->name('events.teams.remove');
    Route::get('/tasks/search', [TileController::class, 'searchTasks'])->name('tasks.search');
    Route::get('/users/search', [UserSearchController::class, 'index'])->name('users.search');

    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/reset', [OnboardingController::class, 'reset'])->name('onboarding.reset');
    Route::get('/onboarding/joinable-boards', [OnboardingController::class, 'joinableBoards'])->name('onboarding.joinable');

    // /profile predates the settings split and is still linked from older
    // places (and any bookmark) — keep it working rather than 404ing.
    Route::redirect('/profile', '/settings/profile');

    Route::get('/settings/profile', [SettingsProfileController::class, 'show'])->name('settings.profile');
    Route::patch('/settings/profile', [SettingsProfileController::class, 'update'])->name('settings.profile.update');
    Route::put('/settings/profile/osrs', [SettingsProfileController::class, 'updateOsrsUsername'])->name('settings.profile.osrs');

    Route::get('/settings/account', [AccountController::class, 'show'])->name('settings.account');
    Route::put('/settings/account/email', [AccountController::class, 'updateEmail'])
        ->middleware('throttle:5,1')
        ->name('settings.account.email');
    Route::put('/settings/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:5,1')
        ->name('settings.account.password');

    // Linking flow reuses DiscordController's existing redirect()/callback() OAuth
    // plumbing rather than a parallel implementation — connect() just stashes
    // which account to attach to before handing off to Discord, and callback()
    // branches on that instead of its normal create-or-login path.
    Route::get('/settings/account/discord/connect', [DiscordController::class, 'connect'])
        ->middleware('throttle:10,1')
        ->name('settings.discord.connect');
    Route::delete('/settings/account/discord', [DiscordController::class, 'disconnect'])->name('settings.discord.disconnect');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::delete('/teams/{team}/members/{userId}', [TeamController::class, 'removeMember'])->name('teams.members.remove');
    Route::get('/teams/{team}/users/search', [TeamController::class, 'searchUsers'])->name('teams.users.search');

    // The whole admin area, behind one middleware rather than a check
    // repeated on every route. Controllers still re-check individually —
    // see EnsureCanAccessAdmin for why that isn't redundant.
    Route::prefix('admin')->name('admin.')->middleware('can-access-admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::post('/users/{user}/roles', [AdminUserController::class, 'assignRole'])->name('users.roles.assign');
        Route::delete('/users/{user}/roles/{role}', [AdminUserController::class, 'removeRole'])->name('users.roles.remove');
        Route::post('/users/{user}/permissions', [AdminUserController::class, 'grantPermission'])->name('users.permissions.grant');
        Route::delete('/users/{user}/permissions/{permissionKey}', [AdminUserController::class, 'revokePermission'])->name('users.permissions.revoke');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/events', [AdminBoardController::class, 'index'])->name('events');
        Route::redirect('/boards', '/admin/events');

        // Tasks is gated on canCreateTiles, not isAdmin (see
        // Admin\TaskController) — an EDITOR reaches this without being an
        // admin, which is why the sidebar filters per item rather than
        // hiding everything behind isAdmin.
        Route::get('/tasks', [AdminTaskController::class, 'index'])->name('tasks');
        Route::post('/tasks', [AdminTaskController::class, 'store'])->name('tasks.store');
        Route::patch('/tasks/{task}', [AdminTaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [AdminTaskController::class, 'destroy'])->name('tasks.destroy');

        Route::get('/site', [SiteSettingsController::class, 'show'])->name('site');
        Route::put('/site', [SiteSettingsController::class, 'update'])->name('site.update');

        Route::get('/content', [ContentController::class, 'index'])->name('content');
        Route::get('/content/{page}', [ContentController::class, 'edit'])->name('content.edit');
        Route::put('/content/{page}', [ContentController::class, 'update'])->name('content.update');

        Route::get('/invites', [InviteController::class, 'index'])->name('invites');
        Route::delete('/invites/{invite}', [InviteController::class, 'destroy'])->name('invites.destroy');

        // Read-only by design — see AuditLogController.
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit');
    });

    // Admin lived under /settings/admin until 2026-08-20. Redirects rather
    // than 404s, since these were linked from the settings sidebar and are
    // plausibly bookmarked.
    Route::redirect('/settings/admin', '/admin');
    Route::redirect('/settings/admin/{path}', '/admin/{path}')->where('path', '.*');
});

// Local-only: logs in as a seeded user without a real Discord round-trip,
// since this environment has no Discord app credentials. Guarded by
// environment() so it can never exist outside local, regardless of the
// password check below — see docs/backlog.md, which tracks removing it
// once Dusk/feature tests cover the auth flow properly instead.
//
// Two identities:
// - ?as=player (default) — the DatabaseSeeder prototype_player, no password
//   needed, matches the original convenience behavior.
// - ?as=admin&pass=... — the AdminUserSeeder account, gated on ADMIN_PASS
//   (.env) as a shared secret. Not real auth security (this whole route
//   only exists in local()), just enough that the login isn't a bare
//   unauthenticated link to an admin session.
if (app()->environment('local')) {
    Route::get('/dev-login', function (Request $request) {
        if ($request->query('as') === 'admin') {
            $expected = env('ADMIN_PASS');
            if (! $expected) {
                abort(400, 'ADMIN_PASS is not set in .env — see .env.example.');
            }
            abort_unless(hash_equals($expected, (string) $request->query('pass', '')), 403, 'Wrong password.');

            $admin = \App\Models\User::where('discord_id', 'local-admin-seed')->first();
            abort_unless($admin, 404, 'No admin test account seeded — run `php artisan db:seed`.');

            Auth::login($admin);

            return redirect('/admin/boards');
        }

        Auth::login(\App\Models\User::where('discord_id', '000000000000000001')->firstOrFail());

        return redirect('/boards');
    });
}

// CMS pages, resolved by slug. LAST in the file on purpose: Laravel matches
// routes in declaration order, so every fixed path above wins and a page slug
// can never shadow a real route. An unknown slug 404s, which is what an
// unmatched single-segment URL should do anyway.

// Everything lived under /boards until 2026-08-20. Declared before the CMS
// catch-all so a stale link redirects rather than 404ing as an unknown page.
Route::redirect('/boards', '/events');
Route::redirect('/my-boards', '/my-events');
Route::redirect('/boards/{path}', '/events/{path}')->where('path', '.*');

Route::get('/{page}', [PageController::class, 'show'])->name('pages.show');
