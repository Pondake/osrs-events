<?php

namespace App\Events\Channels\Concerns;

use App\Models\Event;

/**
 * The event's own details, for channels that stream what is played on it.
 *
 * Every channel streamed its payload — positions, squares, standings — and
 * none of them streamed the event itself. Changing a skill race's end date
 * mid-event reached nobody: the header, the countdown and the "ends in" line
 * all kept the old date until each viewer happened to reload. Reported as
 * "event changes don't seem to come through at all", and they didn't.
 *
 * A version rather than the fields themselves. The pages render these details
 * in a dozen places — header, countdown, cards, the edit modal's defaults —
 * and streaming a partial copy would mean each page picking the changed value
 * out and applying it everywhere by hand. The client asks Inertia for fresh
 * props instead, which is one round trip on an edit that happens once or
 * twice in an event's life, against a channel that polls every few seconds.
 */
trait SignalsEventEdits
{
    /**
     * Changes exactly when something a viewer reads about the event changes.
     *
     * **Read from the database, not from the model handed in.** The stream
     * loads the event once when the connection opens and then calls this
     * every few seconds for the next 45 — so an instance's own attributes are
     * a snapshot of whenever that viewer connected, and a date changed after
     * that would not show up until the connection turned over. Which is
     * exactly what shipped: the edit appeared to arrive, three quarters of a
     * minute late, and the delay looked like the dev server being slow.
     *
     * One row read per poll, which is the same order of cost as the rest of a
     * fingerprint.
     *
     * `updated_at` would be simpler and wrong: a write that touches the row
     * without changing anything on screen would reload every open browser for
     * nothing, which is the one thing a fingerprint must not do.
     */
    protected function eventVersion(Event $stale): string
    {
        // Falls back to the instance when the event has been deleted out from
        // under an open stream: the connection ends on its own timer anyway,
        // and a channel is not the place to decide what a missing event means.
        $event = Event::query()->whereKey($stale->getKey())->first() ?? $stale;

        return md5(implode('|', [
            $event->title,
            $event->description,
            $event->type,
            $event->metric,
            $event->mode,
            $event->access_mode,
            $event->required_guild_id,
            $event->is_listed ? '1' : '0',
            $event->start_date?->toIso8601String(),
            $event->end_date?->toIso8601String(),
            // The one field here that changes mid-event on purpose. A pause
            // has to reach every open browser within seconds — that is the
            // whole point of pausing rather than editing the end date — and
            // this is the line that makes it.
            $event->paused_at?->toIso8601String(),
            $event->pause_reason,
        ]));
    }
}
