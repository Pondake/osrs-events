<?php

use App\Http\Controllers\Settings\Admin\AuditLogController;
use App\Http\Controllers\Settings\Admin\BoardController as AdminBoardController;
use App\Http\Controllers\Settings\Admin\ContentController;
use App\Http\Controllers\Settings\Admin\InviteController;
use App\Http\Controllers\Settings\Admin\SiteSettingsController;
use App\Http\Controllers\Settings\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Settings\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Auth\NewPasswordController;
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

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/privacy', fn () => Inertia::render('Privacy'))->name('privacy');
Route::get('/terms', fn () => Inertia::render('Terms'))->name('terms');

Route::get('/boards', [BoardController::class, 'index'])->name('boards.index');
Route::get('/my-boards', [BoardController::class, 'mine'])
    ->middleware('auth')
    ->name('boards.mine');
Route::get('/boards/{board}', [BoardController::class, 'show'])
    ->middleware('auth')
    ->name('boards.show');
Route::get('/boards/{board}/leaderboard', [LeaderboardController::class, 'show'])
    ->middleware('auth')
    ->name('boards.leaderboard');
Route::get('/boards/{board}/join/{token}', [BoardController::class, 'joinByLink'])->name('boards.join-link');

Route::middleware('auth')->group(function () {
    Route::post('/boards', [BoardController::class, 'store'])->name('boards.store');
    Route::patch('/boards/{board}', [BoardController::class, 'update'])->name('boards.update');
    Route::delete('/boards/{board}', [BoardController::class, 'destroy'])->name('boards.destroy');

    Route::post('/boards/{board}/roll', [PlayerBoardController::class, 'roll'])->name('boards.roll');
    Route::post('/boards/{board}/tiles/{tile}/toggle', [PlayerBoardController::class, 'toggleTile'])->name('boards.tiles.toggle');
    Route::post('/boards/{board}/join', [BoardController::class, 'join'])->name('boards.join');

    Route::get('/boards/{board}/invites', [BoardInviteController::class, 'index'])->name('boards.invites.index');
    Route::post('/boards/{board}/invites', [BoardInviteController::class, 'store'])->name('boards.invites.store');
    Route::delete('/boards/{board}/invites/{invite}', [BoardInviteController::class, 'destroy'])->name('boards.invites.destroy');

    Route::post('/boards/{board}/tiles', [TileController::class, 'upsert'])->name('boards.tiles.upsert');
    Route::delete('/boards/{board}/tiles/{tile}', [TileController::class, 'destroy'])->name('boards.tiles.destroy');

    Route::get('/boards/{board}/teams', [BoardController::class, 'teamsIndex'])->name('boards.teams.index');
    Route::post('/boards/{board}/teams', [BoardController::class, 'addTeam'])->name('boards.teams.add');
    Route::delete('/boards/{board}/teams/{team}', [BoardController::class, 'removeTeam'])->name('boards.teams.remove');
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

    // Everything admin now lives under /settings/admin (see below); these
    // three were top-level nav items until then, so they redirect rather
    // than 404 for anyone with an old link or bookmark.
    Route::prefix('admin')->group(function () {
        Route::redirect('/users', '/settings/admin/users');
        Route::redirect('/boards', '/settings/admin/boards');
        Route::redirect('/tasks', '/settings/admin/tasks');
    });

    // Admin-only settings, rendered in the same SettingsLayout shell as the
    // personal ones. Every action re-checks isAdmin() in the controller;
    // the sidebar only hides the group, it isn't the authorization.
    Route::prefix('settings/admin')->name('settings.admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::post('/users/{user}/roles', [AdminUserController::class, 'assignRole'])->name('users.roles.assign');
        Route::delete('/users/{user}/roles/{role}', [AdminUserController::class, 'removeRole'])->name('users.roles.remove');
        Route::post('/users/{user}/permissions', [AdminUserController::class, 'grantPermission'])->name('users.permissions.grant');
        Route::delete('/users/{user}/permissions/{permissionKey}', [AdminUserController::class, 'revokePermission'])->name('users.permissions.revoke');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/boards', [AdminBoardController::class, 'index'])->name('boards');

        // Tasks is gated on canCreateTiles, not isAdmin (see
        // Admin\TaskController) — an EDITOR reaches this without being an
        // admin, which is why SettingsLayout filters its sidebar per item
        // rather than hiding the whole Administration group behind isAdmin.
        Route::get('/tasks', [AdminTaskController::class, 'index'])->name('tasks');
        Route::post('/tasks', [AdminTaskController::class, 'store'])->name('tasks.store');
        Route::patch('/tasks/{task}', [AdminTaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [AdminTaskController::class, 'destroy'])->name('tasks.destroy');

        Route::get('/site', [SiteSettingsController::class, 'show'])->name('site');
        Route::put('/site', [SiteSettingsController::class, 'update'])->name('site.update');

        Route::get('/content', [ContentController::class, 'index'])->name('content');

        // Read-only by design — see AuditLogController.
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit');

        Route::get('/invites', [InviteController::class, 'index'])->name('invites');
        Route::delete('/invites/{invite}', [InviteController::class, 'destroy'])->name('invites.destroy');
    });
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
