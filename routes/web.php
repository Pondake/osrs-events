<?php

use App\Http\Controllers\Admin\BoardController as AdminBoardController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PlayerBoardController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/auth/discord/redirect', [DiscordController::class, 'redirect'])->name('login');
Route::get('/auth/discord/callback', [DiscordController::class, 'callback'])->name('auth.discord.callback');

Route::post('/logout', function () {
    Auth::logout();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::get('/osrs-snakes-and-ladders', [LandingController::class, 'snakesAndLadders'])
    ->name('landing.snakes');

Route::get('/boards', [BoardController::class, 'index'])->name('boards.index');
Route::get('/boards/{board}', [BoardController::class, 'show'])
    ->middleware('auth')
    ->name('boards.show');

Route::middleware('auth')->group(function () {
    Route::post('/boards', [BoardController::class, 'store'])->name('boards.store');
    Route::patch('/boards/{board}', [BoardController::class, 'update'])->name('boards.update');
    Route::delete('/boards/{board}', [BoardController::class, 'destroy'])->name('boards.destroy');

    Route::post('/boards/{board}/roll', [PlayerBoardController::class, 'roll'])->name('boards.roll');
    Route::post('/boards/{board}/tiles/{tile}/toggle', [PlayerBoardController::class, 'toggleTile'])->name('boards.tiles.toggle');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::delete('/teams/{team}/members/{userId}', [TeamController::class, 'removeMember'])->name('teams.members.remove');

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
