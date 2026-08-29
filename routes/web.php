<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BoardController as AdminBoardController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DiagnosticsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventBlueprintController as AdminEventBlueprintController;
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
use App\Http\Controllers\BingoController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardInviteController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\EventBlueprintController;
use App\Http\Controllers\EventParticipationController;
use App\Http\Controllers\EventStreamController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PlayerBoardController;
use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\Settings\NotificationController;
use App\Http\Controllers\Settings\ProfileController as SettingsProfileController;
use App\Http\Controllers\SiteLockController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SkillRaceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TileController;
use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\WikiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// The pre-launch door. Outside every group that could redirect it, and
// exempted inside EnsureSiteUnlocked itself — a lock screen behind the lock
// is an infinite redirect.
Route::get('/locked', [SiteLockController::class, 'show'])->name('site-lock.show');
Route::post('/locked', [SiteLockController::class, 'unlock'])
    // A shared password is guessable by definition, so this is the one form
    // on the site that genuinely needs a brute-force ceiling.
    ->middleware('throttle:10,1')
    ->name('site-lock.unlock');

// Throttled per-IP — these are unauthenticated by definition, so nothing
// else gates how often they can be hit. `redirect` just builds a URL to
// Discord (cheap, but still a public entry point); `callback` does the
// actual OAuth token exchange + DB writes (new user/guild sync), the more
// expensive and more sensitive of the two, so it gets the tighter limit.
Route::get('/auth/discord/redirect', [DiscordController::class, 'redirect'])
    ->middleware('throttle:20,1')
    ->name('auth.discord.redirect');
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

// Email/password path. `guest` keeps an already-authenticated user from
// landing back on a signup/login form.
//
// `login` names the PAGE, which is the only thing that can safely answer to
// that name. It used to name the Discord kickoff instead, so that everything
// reaching for "send them to log in" — Laravel's own auth middleware,
// redirect()->guest(), every CTA in the app — dropped the user into an OAuth
// consent screen. That was a dead end for anyone holding an email/password
// account, and it sent already-signed-in users through Discord too.
// The OAuth entry point is `auth.discord.redirect`, and pages that mean
// Discord specifically say so.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

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

// Built from the routes and CMS rows rather than a static file. There was no
// sitemap at all in production, which for a site whose traffic plan is
// organic search is the cheapest omission there is to fix.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', [LandingController::class, 'home'])->name('home');
Route::get('/osrs-snakes-and-ladders', [LandingController::class, 'snakesAndLadders'])
    ->name('landing.snakes');
Route::get('/osrs-clan-events', [LandingController::class, 'clanEvents'])->name('landing.clan-events');
Route::get('/osrs-event-ideas', [LandingController::class, 'eventIdeas'])->name('landing.event-ideas');
Route::get('/osrs-bingo', [LandingController::class, 'bingo'])->name('landing.bingo');
Route::get('/osrs-skill-race', [LandingController::class, 'skillRace'])->name('landing.skill-race');
Route::get('/osrs-drop-race', [LandingController::class, 'dropRace'])->name('landing.drop-race');

// /privacy and /terms are CMS pages now, resolved by the /{page} catch-all at
// the bottom of this file — the same path /about already took. Keeping fixed
// routes here would shadow the database rows and quietly serve the old
// hardcoded copy instead.

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
// ~45 seconds per connected viewer. One route for every event type — the
// controller resolves a channel by type and knows nothing else. See
// EventStreamController for why SSE over WebSockets and what it costs.
Route::get('/events/{event}/stream', EventStreamController::class)
    ->middleware(['auth', 'require-osrs-username'])
    ->name('events.stream');
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
    // Stopping an event without ending it. Its own endpoint rather than a
    // field on the update above, because it announces itself to everybody
    // who joined — see BoardController::pause.
    Route::patch('/events/{event}/pause', [BoardController::class, 'pause'])->name('events.pause');

    // Participation is rate limited per user. Not because a player can win
    // by spamming — the game rules already bound what a roll or a claim can
    // do — but because each of these writes changes what the event's live
    // channel fingerprints, so a held-down button pushes a fresh payload to
    // every browser watching that event. One person can make the server work
    // for all of them, which is the part worth capping.
    Route::post('/events/{event}/roll', [PlayerBoardController::class, 'roll'])
        ->middleware('throttle:30,1')
        ->name('events.roll');
    Route::post('/events/{event}/tiles/{tile}/toggle', [PlayerBoardController::class, 'toggleTile'])
        ->middleware('throttle:60,1')
        ->name('events.tiles.toggle');
    Route::patch('/events/{event}/tiles/completions/{completedTile}', [PlayerBoardController::class, 'review'])
        ->name('events.tiles.review');
    // Joining, for every type. Not the same as access: a public event lets
    // anyone look, and this is the person saying they are playing.
    Route::post('/events/{event}/join', [EventParticipationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('events.join');
    Route::delete('/events/{event}/join', [EventParticipationController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('events.leave-event');

    // Bingo. Toggling is a player action gated on access; editing a square
    // or the card is an author action — the same split TileController makes.
    Route::post('/events/{event}/bingo/squares/{square}/claim', [BingoController::class, 'claim'])
        ->middleware('throttle:20,1')
        ->name('events.bingo.claim');
    Route::patch('/events/{event}/bingo/claims/{completion}', [BingoController::class, 'review'])->name('events.bingo.review');
    Route::patch('/events/{event}/bingo/squares/{square}', [BingoController::class, 'updateSquare'])->name('events.bingo.square');
    Route::patch('/events/{event}/bingo', [BingoController::class, 'updateCard'])->name('events.bingo.card');

    // Entering a race is a separate decision from being allowed to look at
    // one — see SkillRaceController::enter.
    // Tightest of the lot: these two are a pure toggle, so they are the
    // easiest to hold down, and entering now costs an outbound Wise Old Man
    // lookup as well — someone else's rate limit, not just ours.
    Route::post('/events/{event}/enter', [SkillRaceController::class, 'enter'])
        ->middleware('throttle:10,1')
        ->name('events.enter');
    // Pulling fresh numbers on demand. Throttled hardest of the lot: one
    // press is one outbound Wise Old Man request per entrant, against a
    // public API whose budget is theirs and not ours.
    Route::post('/events/{event}/standings/sync', [SkillRaceController::class, 'sync'])
        ->middleware('throttle:4,1')
        ->name('events.standings.sync');
    Route::delete('/events/{event}/enter', [SkillRaceController::class, 'leave'])
        ->middleware('throttle:10,1')
        ->name('events.leave');

    Route::get('/events/{event}/invites', [BoardInviteController::class, 'index'])->name('events.invites.index');
    Route::post('/events/{event}/invites', [BoardInviteController::class, 'store'])->name('events.invites.store');
    Route::delete('/events/{event}/invites/{invite}', [BoardInviteController::class, 'destroy'])->name('events.invites.destroy');

    Route::post('/events/{event}/tiles', [TileController::class, 'upsert'])->name('events.tiles.upsert');
    Route::delete('/events/{event}/tiles/{tile}', [TileController::class, 'destroy'])->name('events.tiles.destroy');

    // Who is taking part — teams and people. Its own page rather than a
    // panel, because on a clan event it is a list of forty names and it is
    // also where team management is reached from. Names are withheld from
    // strangers on a public event; see ParticipantController.
    Route::get('/events/{event}/participants', [ParticipantController::class, 'index'])->name('events.participants');

    Route::get('/events/{event}/teams', [BoardController::class, 'teamsIndex'])->name('events.teams.index');
    Route::post('/events/{event}/teams', [BoardController::class, 'addTeam'])->name('events.teams.add');
    Route::delete('/events/{event}/teams/{team}', [BoardController::class, 'removeTeam'])->name('events.teams.remove');
    Route::get('/tasks/search', [TileController::class, 'searchTasks'])->name('tasks.search');

    // The OSRS Wiki picker behind the tile and bingo-square editors. Scoped
    // to an event so the permission is canEditEvent (see WikiController),
    // and throttled because search fires per keystroke against somebody
    // else's volunteer-run server — the service caches on top of this.
    Route::get('/events/{event}/wiki/search', [WikiController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('wiki.search');
    Route::post('/events/{event}/wiki/tasks', [WikiController::class, 'importTask'])
        ->middleware('throttle:60,1')
        ->name('wiki.import');
    // The same search with no event to scope it to — a team icon, a task's
    // own icon field. See WikiController::searchGlobal for why this one
    // needs no per-event permission check.
    Route::get('/wiki/search', [WikiController::class, 'searchGlobal'])
        ->middleware('throttle:60,1')
        ->name('wiki.search.global');
    Route::get('/users/search', [UserSearchController::class, 'index'])->name('users.search');
    // Read side of the admin blueprint list — the create-event form's
    // title autocomplete. Same reasoning as tasks/search above it.
    // The host's side of blueprints — reading the list to start an event,
    // and saving an event as a format. Not the admin controller: that one is
    // the curator's view of the global list and sits behind its own gate.
    Route::get('/event-blueprints', [EventBlueprintController::class, 'suggestions'])->name('blueprints.suggestions');
    Route::post('/events/{event}/blueprint', [EventBlueprintController::class, 'storeFromEvent'])->name('events.blueprint.store');
    Route::get('/my-guilds', [BoardController::class, 'myGuilds'])->name('guilds.mine');

    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/reset', [OnboardingController::class, 'reset'])->name('onboarding.reset');
    Route::get('/onboarding/joinable-boards', [OnboardingController::class, 'joinableBoards'])->name('onboarding.joinable');

    // /profile predates the settings split and is still linked from older
    // places (and any bookmark) — keep it working rather than 404ing.
    Route::redirect('/profile', '/settings/profile');

    Route::get('/settings/profile', [SettingsProfileController::class, 'show'])->name('settings.profile');
    Route::patch('/settings/profile', [SettingsProfileController::class, 'update'])->name('settings.profile.update');
    Route::put('/settings/profile/osrs', [SettingsProfileController::class, 'updateOsrsUsername'])->name('settings.profile.osrs');
    // Re-checks the stored name against Wise Old Man. Throttled because it is
    // a button anyone can hold down, and it spends a request against a shared
    // public API with a 20/minute ceiling.
    Route::post('/settings/profile/osrs/verify', [SettingsProfileController::class, 'verifyOsrsUsername'])
        ->middleware('throttle:6,1')
        ->name('settings.profile.osrs.verify');

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

    // Closing your own account. Throttled hard: it is irreversible, it asks
    // for a password, and a form that takes guesses at one all afternoon is a
    // different feature than the one intended.
    Route::delete('/settings/account', [AccountController::class, 'destroy'])
        ->middleware('throttle:5,1')
        ->name('settings.account.destroy');

    // Settle one owned event/team right now, independent of ever deleting the
    // account — the per-item "confirm" action beside each row on the same
    // page. Same throttle as the account delete itself: these are just as
    // irreversible, one at a time.
    Route::patch('/settings/account/events/{event}', [AccountController::class, 'settleEvent'])
        ->middleware('throttle:20,1')
        ->name('settings.account.events.settle');
    Route::patch('/settings/account/teams/{team}', [AccountController::class, 'settleTeam'])
        ->middleware('throttle:20,1')
        ->name('settings.account.teams.settle');

    // Notifications — the settings page, and the endpoints the browser calls
    // for itself. subscribe/unsubscribe answer JSON rather than an Inertia
    // redirect: they are called from a composable on page load, not from a
    // form, and a redirect there would bounce the page somebody is reading.
    Route::get('/settings/notifications', [NotificationController::class, 'show'])->name('settings.notifications');
    Route::put('/settings/notifications', [NotificationController::class, 'update'])->name('settings.notifications.update');
    Route::delete('/settings/notifications/devices/{subscription}', [NotificationController::class, 'forgetDevice'])
        ->name('settings.notifications.device.forget');
    // Throttled: this button sends a real push to a real device, and holding
    // it down should not become a denial of service against somebody's own
    // lock screen.
    Route::post('/settings/notifications/preview', [NotificationController::class, 'preview'])
        ->middleware('throttle:10,1')
        ->name('settings.notifications.preview');

    Route::get('/push/public-key', [NotificationController::class, 'publicKey'])->name('push.public-key');
    Route::post('/push/subscriptions', [NotificationController::class, 'subscribe'])->name('push.subscribe');
    Route::delete('/push/subscriptions', [NotificationController::class, 'unsubscribe'])->name('push.unsubscribe');

    Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    // Declared above /teams/{team} so the literal segment wins the match —
    // it only collides with the PATCH/DELETE verbs today, but the next GET
    // added there would silently swallow this one.
    Route::get('/teams/options', [TeamController::class, 'options'])->name('teams.options');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::patch('/teams/{team}/members/{userId}', [TeamController::class, 'updateMemberRole'])->name('teams.members.role');
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

        // The only way a site admin edits an event they did not author.
        // Deliberately a separate set of routes rather than an extra
        // permission on the public ones: on the public side an admin is an
        // ordinary user, so reaching for the power means coming in here. The
        // work is not duplicated — each of these asserts admin and hands off
        // to the same controller the public routes use.
        Route::patch('/events/{event}', [AdminBoardController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [AdminBoardController::class, 'destroy'])->name('events.destroy');
        Route::patch('/events/{event}/pause', [AdminBoardController::class, 'pause'])->name('events.pause');
        // Not {event}: route model binding cannot find a trashed row, which
        // is exactly the row this restores. The controller looks it up
        // withTrashed().
        Route::post('/events/{eventId}/restore', [AdminBoardController::class, 'restore'])->name('events.restore');
        Route::get('/events/{event}/teams', [AdminBoardController::class, 'teamsIndex'])->name('events.teams.index');
        Route::post('/events/{event}/teams', [AdminBoardController::class, 'addTeam'])->name('events.teams.add');
        Route::delete('/events/{event}/teams/{team}', [AdminBoardController::class, 'removeTeam'])->name('events.teams.remove');
        Route::get('/events/{event}/invites', [AdminBoardController::class, 'invitesIndex'])->name('events.invites.index');
        Route::post('/events/{event}/invites', [AdminBoardController::class, 'storeInvite'])->name('events.invites.store');
        Route::delete('/events/{event}/invites/{invite}', [AdminBoardController::class, 'destroyInvite'])->name('events.invites.destroy');

        // Tasks is gated on canCreateTiles, not isAdmin (see
        // Admin\TaskController) — an EDITOR reaches this without being an
        // admin, which is why the sidebar filters per item rather than
        // hiding everything behind isAdmin.
        Route::get('/tasks', [AdminTaskController::class, 'index'])->name('tasks');
        Route::post('/tasks', [AdminTaskController::class, 'store'])->name('tasks.store');
        Route::patch('/tasks/{task}', [AdminTaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [AdminTaskController::class, 'destroy'])->name('tasks.destroy');
        // {task} is not route-model-bound here on purpose — implicit binding
        // 404s on a soft-deleted id, which is exactly the row this route
        // needs to reach.
        Route::post('/tasks/{task}/restore', [AdminTaskController::class, 'restore'])->name('tasks.restore');

        // Gated on canCreateBoards, not isAdmin — see the controller.
        Route::get('/blueprints', [AdminEventBlueprintController::class, 'index'])->name('blueprints');
        Route::post('/blueprints', [AdminEventBlueprintController::class, 'store'])->name('blueprints.store');
        Route::patch('/blueprints/{blueprint}', [AdminEventBlueprintController::class, 'update'])->name('blueprints.update');
        Route::delete('/blueprints/{blueprint}', [AdminEventBlueprintController::class, 'destroy'])->name('blueprints.destroy');

        Route::get('/site', [SiteSettingsController::class, 'show'])->name('site');
        Route::put('/site', [SiteSettingsController::class, 'update'])->name('site.update');

        Route::get('/content', [ContentController::class, 'index'])->name('content');
        Route::get('/content/{page}', [ContentController::class, 'edit'])->name('content.edit');
        Route::put('/content/{page}', [ContentController::class, 'update'])->name('content.update');

        Route::get('/invites', [InviteController::class, 'index'])->name('invites');
        Route::delete('/invites/{invite}', [InviteController::class, 'destroy'])->name('invites.destroy');

        // Read-only by design — see AuditLogController.
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit');

        // The "why is nothing happening" page. The checks are reads; the four
        // original actions each address the admin pressing them (their own
        // devices, their own inbox) or are explicitly a rehearsal, so none of
        // them can reach another user. Throttled anyway — these make real
        // outbound requests, and a held-down button should not become one.
        Route::get('/diagnostics', [DiagnosticsController::class, 'index'])->name('diagnostics');
        Route::post('/diagnostics/push', [DiagnosticsController::class, 'testPush'])
            ->middleware('throttle:10,1')
            ->name('diagnostics.push');
        Route::post('/diagnostics/mail', [DiagnosticsController::class, 'testMail'])
            ->middleware('throttle:5,1')
            ->name('diagnostics.mail');
        Route::post('/diagnostics/wom', [DiagnosticsController::class, 'checkWom'])
            ->middleware('throttle:10,1')
            ->name('diagnostics.wom');
        Route::post('/diagnostics/sweep', [DiagnosticsController::class, 'sweep'])
            ->middleware('throttle:10,1')
            ->name('diagnostics.sweep');

        // The one part of this page that reaches somebody else — see
        // DiagnosticsController's own class docs for why these three are
        // guarded differently from the four above.
        Route::get('/diagnostics/standings', [DiagnosticsController::class, 'standingsFailures'])
            ->name('diagnostics.standings');
        Route::post('/diagnostics/standings/{user}/nudge', [DiagnosticsController::class, 'nudgeStandingsFailure'])
            ->middleware('throttle:20,1')
            ->name('diagnostics.standings.nudge');
        Route::delete('/diagnostics/standings/{user}/username', [DiagnosticsController::class, 'resetStandingsUsername'])
            ->middleware('throttle:20,1')
            ->name('diagnostics.standings.reset');
    });

    // Admin lived under /settings/admin until 2026-08-20. Redirects rather
    // than 404s, since these were linked from the settings sidebar and are
    // plausibly bookmarked.
    Route::redirect('/settings/admin', '/admin');
    Route::redirect('/settings/admin/{path}', '/admin/{path}')->where('path', '.*');
});

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

/**
 * Everything else — a branded 404 instead of a bare one.
 *
 * `/{page}` above already catches every single-segment URL and 404s from
 * PageController, so this is really about the deeper ones (`/events/nope/x`).
 * Without a route to match, Laravel throws before the `web` group runs, and
 * the error page then renders with no session and no shared Inertia props —
 * a signed-in user gets a 404 wearing a signed-out header. Matching here puts
 * the miss back inside the middleware stack, where the shell knows who is
 * looking at it.
 *
 * The rendering is the exception handler's job, not this closure's — see
 * bootstrap/app.php.
 */
Route::fallback(fn () => abort(404))->name('miss');
