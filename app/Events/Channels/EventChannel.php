<?php

namespace App\Events\Channels;

use App\Models\Event;

/**
 * A live channel for one kind of event.
 *
 * Every event type gets one. The stream controller knows nothing about skill
 * races or bingo cards — it resolves a channel for the event's type and asks
 * it two questions on a timer:
 *
 *   fingerprint() — has anything the viewer can see changed?
 *   payload()     — what should they see now?
 *
 * Splitting those two is the whole point. The fingerprint runs every few
 * seconds per connected viewer, so it has to be cheap; the payload only runs
 * when something actually changed. A channel that built the full payload just
 * to decide whether to send it would do the expensive half constantly.
 */
interface EventChannel
{
    /**
     * A value that changes exactly when the rendered view changes.
     *
     * Must be derived from what the client *displays*, not from every column:
     * a sync that rewrites a timestamp without changing a score must not wake
     * every open browser.
     */
    public function fingerprint(Event $event): string;

    /** What to push. The shape is the channel's own business. */
    public function payload(Event $event): array;

    /**
     * The SSE event name clients listen for. Distinct per channel so a page
     * only handles messages meant for it.
     */
    public function name(): string;
}
