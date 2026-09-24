<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PluginTestReport;
use App\Support\PluginTestSet;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/** Every tester's run of the plugin test set, expected reports against received ones. */
class PluginTestController extends Controller implements HasMiddleware
{
    /** Admin only; the /admin group also lets creators and editors in. */
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, Closure $next) {
                abort_unless($request->user()?->isAdmin(), 403);

                return $next($request);
            }),
        ];
    }

    public function index(PluginTestReport $tests): Response
    {
        $event = $tests->event();

        return Inertia::render('Admin/PluginTests', [
            'mode' => Setting::get('runelite_plugin_mode'),
            'event' => $event === null ? null : ['id' => $event->id, 'title' => $event->title, 'url' => "/events/{$event->id}"],
            'eventTitle' => PluginTestSet::EVENT_TITLE,
            'testers' => $tests->all(),
        ]);
    }
}
