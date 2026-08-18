<?php

use App\Http\Controllers\Admin\BoardController as AdminBoardController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardInviteController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PlayerBoardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/auth/discord/redirect', [DiscordController::class, 'redirect'])->name('login');
Route::get('/auth/discord/callback', [DiscordController::class, 'callback'])->name('auth.discord.callback');

Route::post('/logout', function () {
    Auth::logout();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::get('/', [LandingController::class, 'home'])->name('home');
Route::get('/osrs-snakes-and-ladders', [LandingController::class, 'snakesAndLadders'])
    ->name('landing.snakes');
Route::get('/osrs-clan-events', [LandingController::class, 'clanEvents'])->name('landing.clan-events');
Route::get('/osrs-event-ideas', [LandingController::class, 'eventIdeas'])->name('landing.event-ideas');

Route::get('/about', fn () => Inertia::render('About'))->name('about');
Route::get('/donate', fn () => Inertia::render('Donate'))->name('donate');
Route::get('/privacy', fn () => Inertia::render('Privacy'))->name('privacy');
Route::get('/terms', fn () => Inertia::render('Terms'))->name('terms');

Route::get('/boards', [BoardController::class, 'index'])->name('boards.index');
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

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::delete('/teams/{team}/members/{userId}', [TeamController::class, 'removeMember'])->name('teams.members.remove');
    Route::get('/teams/{team}/users/search', [TeamController::class, 'searchUsers'])->name('teams.users.search');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/boards', [AdminBoardController::class, 'index'])->name('boards.index');

        Route::get('/tasks', [AdminTaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [AdminTaskController::class, 'store'])->name('tasks.store');
        Route::patch('/tasks/{task}', [AdminTaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [AdminTaskController::class, 'destroy'])->name('tasks.destroy');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/roles', [AdminUserController::class, 'assignRole'])->name('users.roles.assign');
        Route::delete('/users/{user}/roles/{role}', [AdminUserController::class, 'removeRole'])->name('users.roles.remove');
        Route::post('/users/{user}/permissions', [AdminUserController::class, 'grantPermission'])->name('users.permissions.grant');
        Route::delete('/users/{user}/permissions/{permissionKey}', [AdminUserController::class, 'revokePermission'])->name('users.permissions.revoke');
    });
});

// Local-only: logs in the seeded prototype user without a real Discord
// round-trip, since this environment has no Discord app credentials.
// Guarded by environment() so it can never exist outside local — see
// docs/backlog.md, which tracks removing it once Dusk/feature tests cover
// the auth flow properly instead.
if (app()->environment('local')) {
    Route::get('/dev-login', function () {
        Auth::login(\App\Models\User::where('discord_id', '000000000000000001')->firstOrFail());

        return redirect('/boards');
    });
}
