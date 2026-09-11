<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the whole /admin area.
 *
 * Deliberately broader than isAdmin(): an EDITOR with `canCreateTiles` needs
 * the Tasks page and nothing else there, and someone with `canCreateBoards`
 * needs Blueprints. This middleware only decides who may see the area at
 * all — each controller still makes its own finer check, so that EDITOR gets
 * 200 on tasks and 403 on everything else.
 *
 * The per-controller checks are not redundant. Route middleware is easy to
 * forget on a newly added route; a controller that also checks cannot be
 * reached unguarded by accident.
 */
class EnsureCanAccessAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // canCreateBoards joins the list for the same reason canCreateTiles
        // is on it: event blueprints are managed in here and are the board
        // creators' own reference data, not an admin setting.
        abort_unless($user && (
            $user->isAdmin()
            || $user->hasPermission('canCreateTiles')
            || $user->hasPermission('canCreateBoards')
        ), 403);

        // What the search box in the admin navbar searches. Labels only,
        // never values.
        Inertia::share('settingsIndex', fn () => Setting::searchIndex());

        return $next($request);
    }
}
