<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventStanding;
use App\Models\User;
use App\Support\NotificationCategory;
use App\Support\Ordinal;
use App\Support\PushMessage;

/**
 * "Somebody passed you" — the one notification that had to be designed
 * against itself.
 *
 * A metric race resyncs every ten minutes and positions further down a
 * leaderboard swap constantly, so the naive version of this — notify on any
 * rank change — would be the single loudest thing in the app and would take
 * every other category down with it when people revoked permission.
 *
 * So it fires on **boundaries people care about**, not on movement:
 *
 *  - you were first and are not any more
 *  - you were on the podium and have fallen off it
 *
 * Nothing else. Sliding from 14th to 15th is a fact the open page already
 * shows, live, at no cost to anyone. The category is also off by default and
 * carries an hourly per-event throttle on top of this.
 */
class RaceRankNotifier
{
    public function __construct(private readonly PushNotifier $notifier) {}

    /**
     * Ranks as they stand right now, keyed by user.
     *
     * Deliberately a plain array taken before and after a sync rather than a
     * stored column: the comparison is only meaningful within one run, and a
     * persisted "last known rank" would go stale the moment anything else
     * touched the table.
     *
     * @return array<string, int>
     */
    public function snapshot(Event $event): array
    {
        return $this->ranks($event);
    }

    /**
     * Compare against a snapshot and tell whoever lost ground that matters.
     *
     * @param  array<string, int>  $before
     * @return array{sent: int, expired: int, failed: int, skipped: int}
     */
    public function announce(Event $event, array $before): array
    {
        $result = WebPushService::emptyResult();

        if ($before === []) {
            return $result;
        }

        $after = $this->ranks($event);
        $leaderName = $this->leaderName($event);

        foreach ($after as $userId => $rank) {
            $was = $before[$userId] ?? null;

            // No previous rank means they were unmeasured before — a first
            // successful sync, not a loss. Telling somebody they "dropped
            // out of the top three" the first time they were ever ranked
            // would be both wrong and impossible to interpret.
            if ($was === null || $rank <= $was) {
                continue;
            }

            $message = match (true) {
                $was === 1 => new PushMessage(
                    title: trans('notifications.push_rank_lost_first_title'),
                    body: trans('notifications.push_rank_lost_first_body', [
                        'rival' => $leaderName ?? trans('notifications.someone'),
                        'event' => $event->title,
                    ]),
                    path: "/events/{$event->id}",
                    category: NotificationCategory::RANK_CHANGE,
                    // Per event, so two races can each report a loss within
                    // the hour but one race cannot report twice.
                    tag: 'rank:'.$event->id,
                ),
                $was <= 3 && $rank > 3 => new PushMessage(
                    title: trans('notifications.push_rank_lost_podium_title'),
                    body: trans('notifications.push_rank_lost_podium_body', [
                        'place' => Ordinal::of($rank),
                        'event' => $event->title,
                    ]),
                    path: "/events/{$event->id}",
                    category: NotificationCategory::RANK_CHANGE,
                    tag: 'rank:'.$event->id,
                ),
                default => null,
            };

            if ($message === null) {
                continue;
            }

            $user = User::find($userId);

            if ($user === null) {
                continue;
            }

            $result = WebPushService::merge($result, $this->notifier->toUser($user, $message));
        }

        return $result;
    }

    /**
     * The same ranking the standings page shows, keyed by user id.
     *
     * The ordering and the tie handling are copied from
     * EventStandingsService::forEvent deliberately rather than reused: that
     * method returns display rows keyed by standing id with no user id on
     * them, and threading one through would change a shape the SSE payload
     * and three components already depend on. If the two ever disagree, the
     * page is right and this is the copy to fix.
     *
     * @return array<string, int>
     */
    private function ranks(Event $event): array
    {
        $rows = EventStanding::query()
            ->where('event_id', $event->id)
            ->whereNotNull('user_id')
            // Unmeasured rows sort to the bottom and are left unranked —
            // otherwise their gained of 0 ties them with everyone who really
            // gained nothing and takes a place off the people competing.
            ->orderByRaw('case when sync_error is not null or synced_at is null then 1 else 0 end')
            ->orderByDesc('gained')
            ->orderBy('username')
            ->get(['user_id', 'gained', 'sync_error', 'synced_at']);

        $ranks = [];
        $rank = 0;
        $seen = 0;
        $previous = null;

        foreach ($rows as $row) {
            if ($row->sync_error !== null || $row->synced_at === null) {
                continue;
            }

            $seen++;

            if ($row->gained !== $previous) {
                $rank = $seen;
                $previous = $row->gained;
            }

            $ranks[$row->user_id] = $rank;
        }

        return $ranks;
    }

    /** Whoever is top now — the "who passed me" half of the sentence. */
    private function leaderName(Event $event): ?string
    {
        $leader = EventStanding::query()
            ->where('event_id', $event->id)
            ->whereNull('sync_error')
            ->whereNotNull('synced_at')
            ->orderByDesc('gained')
            ->orderBy('username')
            ->first();

        return $leader?->username;
    }
}
