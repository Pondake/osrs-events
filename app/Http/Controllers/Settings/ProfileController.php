<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Rules\OsrsUsername;
use App\Services\OsrsIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Display-side settings (name, roles, joined boards). Account-side settings
 * — auth methods, password — live in AccountController next to this one;
 * both render inside Components/SettingsLayout.vue's sidebar shell.
 */
class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user()->load('roles:id,name');

        // Built from events, not PlayerBoard rows. Two things were wrong with
        // the old list, and both came from the event/board split:
        //
        //  - `boards` has no `title` column any more — it lives on the event
        //    — so every row rendered with a blank name.
        //  - it linked to /events/{board id}. Those matched only for rows
        //    migrated at the split; every board created since gets its own
        //    uuid, so the link 404'd.
        //
        // And being PlayerBoard-shaped, it could only ever list Snakes &
        // Ladders: races and bingo cards were absent from "your boards"
        // entirely, which is what "I made a skill event and it is not linked
        // to me" was actually showing.
        $tileCounts = ['SIZE_5X5' => 25, 'SIZE_7X7' => 49, 'SIZE_9X9' => 81];

        $playerBoards = $user->playerBoards()->with('completedTiles')->get()->keyBy('board_id');

        $events = Event::involving($user)
            ->with(['board', 'authors'])
            ->orderByDesc('start_date')
            ->get()
            ->map(function (Event $event) use ($playerBoards, $tileCounts, $user) {
                $progress = null;

                // Progress only where there is a board to progress across —
                // a race has a rank, not a position, and inventing a
                // percentage for it would be a made-up number.
                if ($event->board && ($pb = $playerBoards->get($event->board->id))) {
                    $total = $tileCounts[$event->board->size] ?? 49;
                    $position = max(0, $pb->current_position);

                    $progress = [
                        'position' => $position + 1,
                        'total' => $total,
                        'completed' => $pb->completedTiles->count(),
                        // Capped at 99 until the last tile is actually done,
                        // the same rule the hub uses.
                        'pct' => $total <= 1 ? 0 : min(99, (int) floor(($position / ($total - 1)) * 100)),
                    ];
                }

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'type' => $event->type,
                    'size' => $event->board?->size,
                    'isOwner' => $event->authors->contains(
                        fn ($author) => $author->user_id === $user->id && $author->is_owner,
                    ),
                    'progress' => $progress,
                ];
            })
            ->values();

        return Inertia::render('Settings/Profile', [
            'roles' => $user->roles->pluck('name'),
            'events' => $events,
            // Not shared globally via HandleInertiaRequests: this is the only
            // page that edits it, and the skill-race page gets its own copy.
            'osrsUsername' => $user->osrs_username,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['nickname' => ['nullable', 'string', 'max:255']]);

        $request->user()->update(['nickname' => $data['nickname'] ?: null]);

        return back()->with('board-save', trans('profile.nickname_saved'));
    }

    /**
     * The OSRS account name, kept on its own endpoint rather than folded into
     * update() above.
     *
     * Two forms writing through one validated action is how a field gets
     * wiped: validate() returns only the keys it has rules for, so a nickname
     * save that also listed osrs_username would blank it whenever the
     * nickname form didn't send one.
     */
    public function updateOsrsUsername(Request $request, OsrsIdentityService $identity): RedirectResponse
    {
        $data = $request->validate([
            // Required rather than nullable: every account has one by the
            // time it gets here (RequireOsrsUsername sees to that), so
            // allowing a blank would let someone quietly undo it and drop
            // out of every race they had entered.
            'osrs_username' => ['required', 'string', new OsrsUsername],
        ]);

        $found = $identity->apply($request->user(), $data['osrs_username']);

        // Saved regardless; an unconfirmed name is a warning, not a rejection.
        return $found === false
            ? back()->with('board-save-error', trans('auth.osrs_not_found'))
            : back()->with('board-save', trans('profile.osrs_username_saved'));
    }

    /**
     * Re-run the Wise Old Man check on the name already stored — the action
     * behind the recurring "we can't find this account" notice.
     */
    public function verifyOsrsUsername(Request $request, OsrsIdentityService $identity): RedirectResponse
    {
        $found = $identity->recheck($request->user());

        return match ($found) {
            true => back()->with('board-save', trans('auth.osrs_found')),
            false => back()->with('board-save-error', trans('auth.osrs_not_found')),
            // Their API was unreachable. Saying "not found" here would tell
            // someone their own RSN is wrong because a third party was down.
            default => back()->with('board-save-error', trans('auth.osrs_check_failed')),
        };
    }
}
