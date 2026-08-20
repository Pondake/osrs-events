<?php

namespace App\Http\Controllers\Settings\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Placeholder home for the eventual CMS (roadmap Phase 6 — see
 * docs/backlog.md). Right now it only inventories which pages exist and
 * states plainly that they're still hardcoded Vue components; it does NOT
 * pretend to edit anything. The list is hardcoded here on purpose: there is
 * no content table to read from yet, and inventing one before the editor's
 * shape is decided would be the wrong way round.
 */
class ContentController extends Controller
{
    public function index(): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return Inertia::render('Settings/Admin/Content', [
            'pages' => [
                ['path' => '/', 'label' => trans('nav.home')],
                ['path' => '/osrs-snakes-and-ladders', 'label' => trans('nav.snakes')],
                ['path' => '/osrs-clan-events', 'label' => trans('nav.clan_events')],
                ['path' => '/osrs-event-ideas', 'label' => trans('nav.event_ideas')],
                ['path' => '/about', 'label' => trans('about.title')],
                ['path' => '/privacy', 'label' => trans('privacy.title')],
                ['path' => '/terms', 'label' => trans('terms.title')],
            ],
        ]);
    }
}
