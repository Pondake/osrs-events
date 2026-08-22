<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventParticipationService;
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
