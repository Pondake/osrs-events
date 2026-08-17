<?php

use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\LandingController;
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

Route::get('/boards/{board}', [BoardController::class, 'show'])
    ->middleware('auth')
    ->name('boards.show');
