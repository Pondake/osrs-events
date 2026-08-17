<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\LandingController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Prototype-only stand-in for Discord OAuth (osrs-events' real login flow).
// Just enough to exercise an authenticated, session-dependent SSR page —
// evaluating Socialite + Discord wiring is out of scope for this slice.
Route::get('/login', function () {
    $user = User::firstOrCreate(
        ['email' => 'prototype@osrs-events.test'],
        ['name' => 'Prototype Player', 'password' => bcrypt(str()->random(32))],
    );

    Auth::login($user);

    return redirect()->intended('/');
})->name('login');

Route::get('/osrs-snakes-and-ladders', [LandingController::class, 'snakesAndLadders'])
    ->name('landing.snakes');

Route::get('/boards/{board}', [BoardController::class, 'show'])
    ->middleware('auth')
    ->name('boards.show');
