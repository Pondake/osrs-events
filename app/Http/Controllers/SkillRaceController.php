<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventParticipationService;
use App\Services\EventStandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Entering and leaving a metric race.
 *
 * The live channel moved to EventStreamController when every event type got
 * one — this is now only the two actions that change who is competing.
 */
class SkillRaceController extends Controller
{
    /**
     * Enter the current user into the race.
     *
     * Kept as its own route because a race is the one type where entering
     * has a precondition — an OSRS name to look the numbers up by — and the
     * 404 below is what says "this event is not raced". The work itself is
     * the same join every other type does; see EventParticipationService.
     */
    public function enter(
        Request $request,
        Event $event,
        EventParticipationService $participation,
    ): RedirectResponse {
        // Both metric types, not just skill races — a drop race is entered
        // exactly the same way.
        abort_unless($event->needsMetric(), 404);

        try {
            $participation->join($request->user(), $event);
        } catch (ValidationException $e) {
            // A toast, because there is no field on this page to hang an
            // error message under.
            return back()->with('board-save-error', collect($e->errors())->flatten()->first());
        }

        return back()->with('board-save', trans('events.entered'));
    }

    /**
     * Pull fresh numbers from Wise Old Man for this race, now.
     *
     * A host who has just moved the dates or changed the metric is looking at
     * a table measured against the old ones, and the scheduled sync runs on
     * its own clock — so "is what I am looking at true?" had no answer except
     * waiting. This is that answer.
     *
     * A host action rather than a player one, and throttled hard, because
     * every press spends somebody else's public API budget: one click on a
     * forty-entrant race is forty outbound requests.
     */
    public function sync(
        Request $request,
        Event $event,
        EventStandingsService $standings,
    ): RedirectResponse {
        abort_unless($event->needsMetric(), 404);
        $this->assertCanEditEvent($request->user(), $event);

        // A paused race is a race whose scoreboard must not move — the same
        // rule the scheduled command follows.
        if ($event->isPaused()) {
            return back()->with('board-save-error', trans('events.paused_notice'));
        }

        $result = $standings->syncAll($event);

        // Named, not counted. "Updated 5 of 6" is a status; "Not A Player is
        // not tracked" is something a host can go and fix.
        if ($result['failed'] > 0) {
            $names = collect($result['failures'])->pluck('name')->take(3)->implode(', ');
            $extra = $result['failed'] > 3 ? trans('events.sync_and_more', ['count' => $result['failed'] - 3]) : '';

            return back()->with('board-save-error', trans('events.sync_partial', [
                'synced' => $result['synced'],
                'total' => $result['synced'] + $result['failed'],
                'names' => $names.$extra,
            ]));
        }

        return back()->with('board-save', trans('events.sync_done', ['count' => $result['synced']]));
    }

    public function leave(
        Request $request,
        Event $event,
        EventParticipationService $participation,
    ): RedirectResponse {
        abort_unless($event->needsMetric(), 404);

        try {
            $participation->leave($request->user(), $event);
        } catch (ValidationException $e) {
            return back()->with('board-save-error', collect($e->errors())->flatten()->first());
        }

        return back()->with('board-save', trans('events.left'));
    }
}
