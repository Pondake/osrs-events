<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BossIcon;
use App\Models\Event;
use App\Services\BossIconService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Setting a boss's icon by hand.
 *
 * Exists because the committed pet sprites come from a package, and a package
 * lags the game: two bosses have a pet on the wiki that has not shipped yet,
 * and new bosses arrive with every update. Without this, filling one in means
 * editing a script, re-running it and deploying — for a picture.
 *
 * Admin-only, unlike Tasks and Blueprints which an editor reaches: this is
 * site-wide presentation, not content somebody makes for their own event.
 */
class BossIconController extends Controller
{
    public function index(BossIconService $icons): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return Inertia::render('Admin/BossIcons', [
            'bosses' => $icons->all(),
        ]);
    }

    /**
     * Store or replace one boss's icon.
     *
     * Upsert rather than create-or-update by id: the metric is the identity
     * here, and a form that has to know whether a row already exists is a form
     * that can get it wrong.
     */
    public function update(Request $request, BossIconService $icons): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $data = $request->validate([
            // Against the real list, so a typo cannot create a row for a boss
            // that does not exist and then never render anywhere.
            'metric' => ['required', 'string', Rule::in(Event::BOSS_METRICS)],
            // http/https only, same rule the Ko-fi URL follows — anything else
            // either does not load or is a scheme no <img> should be handed.
            'icon_url' => ['required', 'url:http,https', 'max:2048'],
        ]);

        BossIcon::updateOrCreate(
            ['metric' => $data['metric']],
            ['icon_url' => $data['icon_url']],
        );

        return back()->with('board-save', trans('admin.boss_icon_saved'));
    }

    /**
     * Accept a proposal from the weekly check, putting it in force.
     *
     * The proposal is moved rather than copied: once it is the icon it is no
     * longer waiting on anybody, and leaving it in both columns would keep the
     * row at the top of the queue forever.
     */
    public function approve(Request $request, string $metric, BossIconService $icons): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $row = BossIcon::where('metric', $metric)->whereNotNull('suggested_url')->firstOrFail();

        // Accepting the packaged sprite REMOVES the override rather than
        // storing the sprite's own path as one. Otherwise the boss would show
        // the right picture for the wrong reason — a hand-set value that
        // happens to point at the file — and would stop following the package
        // the next time the sprite changed.
        if ($row->suggested_url === $icons->spriteUrl($metric)) {
            $row->update(['icon_url' => null, 'suggested_url' => null]);

            return back()->with('board-save', trans('admin.boss_icon_reset'));
        }

        $row->update(['icon_url' => $row->suggested_url, 'suggested_url' => null]);

        return back()->with('board-save', trans('admin.boss_icon_saved'));
    }

    /**
     * Turn a proposal down, and remember which one.
     *
     * The URL is kept in `dismissed_url` so the weekly check does not offer
     * the same picture again next Monday. A different one for the same boss
     * still can — the memory is of an image, not of a decision to stop
     * looking.
     */
    public function dismiss(Request $request, string $metric): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $row = BossIcon::where('metric', $metric)->whereNotNull('suggested_url')->firstOrFail();

        $row->update(['dismissed_url' => $row->suggested_url, 'suggested_url' => null]);

        // Nothing left on the row at all: no icon, no proposal. Keeping it
        // would only be keeping the dismissal, which is the point.
        return back()->with('board-save', trans('admin.boss_icon_dismissed'));
    }

    /**
     * Drop an override, falling back to the committed sprite.
     *
     * Not "clear the icon": deleting the row restores whatever the package
     * ships, which for 61 of these is a real pet. Only the ten without a
     * sprite actually go blank.
     */
    public function destroy(Request $request, string $metric): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        BossIcon::where('metric', $metric)->delete();

        return back()->with('board-save', trans('admin.boss_icon_reset'));
    }
}
