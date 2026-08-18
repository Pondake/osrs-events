<?php

use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PlayerBoardController;
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
