<?php

use App\Http\Controllers\Api\RunelitePluginController;
use App\Http\Middleware\AuthenticatePluginToken;
use Illuminate\Support\Facades\Route;

Route::prefix('plugin/v1')
    ->middleware([AuthenticatePluginToken::class, 'throttle:runelite-plugin'])
    ->group(function () {
        Route::get('/events', [RunelitePluginController::class, 'events']);
        Route::post('/completions', [RunelitePluginController::class, 'complete']);
    });
