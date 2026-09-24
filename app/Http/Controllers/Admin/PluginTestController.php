<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\PluginTestReport;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Everything the plugin sent while it is in testing: every account, every
 * event, every report, and each account judged against the test set.
 *
 * Only while testing. Once the plugin is live these are ordinary players'
 * reports, and an admin page listing all of them is not what testing agreed to.
 */
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

    public function index(Request $request, PluginTestReport $tests): Response
    {
        $mode = Setting::get('runelite_plugin_mode');
        $testers = $mode === 'testing' ? $tests->testers() : [];
        $selectedId = $request->query('tester', $testers[0]['user']['id'] ?? null);
        $selected = collect($testers)->contains(fn (array $row) => $row['user']['id'] === $selectedId)
            ? User::find($selectedId)
            : null;
        $event = $tests->event();

        return Inertia::render('Admin/PluginTests', [
            'mode' => $mode,
            'testSet' => $event === null ? null : ['title' => $event->title, 'url' => "/events/{$event->id}"],
            'testers' => $testers,
            'selected' => $selected === null ? null : $tests->forUser($selected),
        ]);
    }
}
