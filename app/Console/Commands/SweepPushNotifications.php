<?php

namespace App\Console\Commands;

use App\Models\BingoCompletion;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\PlayerBoard;
use App\Models\User;
use App\Services\PushNotifier;
use App\Services\WebPushService;
use App\Support\NotificationCategory;
use App\Support\Ordinal;
use App\Support\PushMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * The five notifications that nothing else can trigger.
 *
 * A claim being reviewed has a controller action to hang off; an event
 * starting does not. These five are all answers to "has enough time passed
 * that somebody should be told", which only a clock can ask:
 *
 *   - an event is about to start, or about to end
 *   - an event has finished and the standings are final
 *   - claims have been sitting in a host's queue
 *   - an event's standings have stopped being trustworthy
 *   - somebody's daily rolls came back
 *
 * **Idempotency is the whole design problem here.** The sweep runs every
 * fifteen minutes against windows measured in hours, so every check would
 * fire four times without something to stop it. Two mechanisms do, and they
 * are not interchangeable:
 *
 *   - `Cache::add()` at the event level, for the once-per-event facts
 *     (starting, ending, final result). Atomic, and it stops the work as well
 *     as the send.
 *   - the per-user throttle in PushNotifier, for the recurring ones (a review
 *     queue that is still not empty an hour later genuinely should say so
 *     again).
 *
 * Everything in here is wrapped: this is unattended work, and one event with
 * bad data must not stop the other twenty from being swept.
 */
class SweepPushNotifications extends Command
{
    protected $signature = 'push:sweep {--dry-run : Report what would be sent without sending it}';

    protected $description = 'Send the time-based push notifications (event start/end, review queues, stale standings, daily rolls)';

    /** How close to the start or the end counts as "about to". */
    private const HORIZON_MINUTES = 60;

    /**
     * Not before this hour, server time.
     *
     * "Your rolls are back" is true at midnight, which is the one time it
     * must not be said. A single hour gate is a deliberately crude answer to
     * timezones — this app has no per-user timezone, and inventing one for a
     * reminder would be a schema change to solve a politeness problem.
     */
    private const ROLLS_FROM_HOUR = 8;

    private array $totals;

    public function handle(PushNotifier $push, WebPushService $webPush): int
    {
        $this->totals = WebPushService::emptyResult();

        if (! $webPush->isConfigured()) {
            // Not a failure. It is what a fresh clone and a fresh deploy both
            // look like, and a scheduled command that errors nightly on a dev
            // box trains people to ignore the scheduler.
            $this->info('No VAPID keys configured — nothing to sweep.');

            return self::SUCCESS;
        }

        $this->sweep('starting events', fn () => $this->eventsStarting($push));
        $this->sweep('ending events', fn () => $this->eventsEnding($push));
        $this->sweep('finished events', fn () => $this->eventsFinished($push));
        $this->sweep('review queues', fn () => $this->reviewQueues($push));
        $this->sweep('standings health', fn () => $this->standingsHealth($push));
        $this->sweep('daily rolls', fn () => $this->dailyRolls($push));

        if ($this->option('dry-run')) {
            // Said once rather than per line, but said: what follows is what
            // the QUERIES found, before PushNotifier applies each user's
            // category preference and throttle. Most of the daily-roll lines
            // above will not actually be sent, because that category is off
            // by default — a dry run that implied otherwise would be a
            // diagnostic tool telling a comfortable lie.
            $this->line('');
            $this->comment('Dry run: per-user preferences and throttles are NOT applied above.');
            $this->comment('The real sweep sends only to people who have that category switched on.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Sent %d, %d expired, %d failed, %d skipped.',
            $this->totals['sent'],
            $this->totals['expired'],
            $this->totals['failed'],
            $this->totals['skipped'],
        ));

        return self::SUCCESS;
    }

    private function sweep(string $label, callable $work): void
    {
        try {
            $work();
        } catch (Throwable $error) {
            $this->error("  {$label}: {$error->getMessage()}");
            report($error);
        }
    }

    /**
     * "Your event starts in an hour."
     *
     * Deliberately not "your event has started": the point is the hour of
     * warning — time to log in, bank, and be at the right place when the
     * clock starts. Arriving at the moment it begins is a notification about
     * something already missed.
     */
    private function eventsStarting(PushNotifier $push): void
    {
        $events = Event::query()
            ->whereNull('paused_at')
            ->whereNotNull('start_date')
            ->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMinutes(self::HORIZON_MINUTES)])
            ->get();

        foreach ($events as $event) {
            if (! $this->claim('start', $event)) {
                continue;
            }

            $this->deliver("start of {$event->title}", fn () => $push->toParticipants($event, new PushMessage(
                title: trans('notifications.push_event_starting_title', ['event' => $event->title]),
                body: trans('notifications.push_event_starting_body'),
                path: "/events/{$event->id}",
                category: NotificationCategory::EVENT_SCHEDULE,
                tag: 'schedule:start:'.$event->id,
            )));
        }
    }

    /**
     * "One hour left."
     *
     * The one notification in this app with a straightforward reason to
     * exist: in a metric race an hour of focused play genuinely changes the
     * result, and the person who does not know the clock is running out
     * cannot spend it.
     */
    private function eventsEnding(PushNotifier $push): void
    {
        $events = Event::query()
            ->whereNull('paused_at')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [Carbon::now(), Carbon::now()->addMinutes(self::HORIZON_MINUTES)])
            ->get();

        foreach ($events as $event) {
            if (! $this->claim('end', $event)) {
                continue;
            }

            $this->deliver("end of {$event->title}", fn () => $push->toParticipants($event, new PushMessage(
                title: trans('notifications.push_event_ending_title', ['event' => $event->title]),
                body: trans('notifications.push_event_ending_body'),
                path: "/events/{$event->id}",
                category: NotificationCategory::EVENT_SCHEDULE,
                tag: 'schedule:end:'.$event->id,
            )));
        }
    }

    /**
     * "You finished 3rd."
     *
     * Waits a full hour past the end rather than firing on the stroke: the
     * standings sync runs every ten minutes, and announcing a placing built
     * from numbers that had not been refreshed since before the finish would
     * be wrong in exactly the way people would remember.
     */
    private function eventsFinished(PushNotifier $push): void
    {
        $events = Event::query()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [Carbon::now()->subHours(6), Carbon::now()->subHour()])
            ->with('participants.user')
            ->get();

        foreach ($events as $event) {
            if (! $this->claim('result', $event, hours: 24)) {
                continue;
            }

            // Placings only exist for the types that rank. A bingo card or a
            // board would need its own scoring pass, and a wrong placing is
            // worse than none — so those get the plain "it's over" line.
            $places = in_array($event->type, Event::metricTypes(), true)
                ? $this->placings($event)
                : [];

            $total = count($places);

            foreach ($event->participants as $participant) {
                $user = $participant->user;

                if ($user === null) {
                    continue;
                }

                $place = $places[$user->id] ?? null;

                $this->deliver("result of {$event->title}", fn () => $push->toUser($user, new PushMessage(
                    title: trans('notifications.push_event_result_title', ['event' => $event->title]),
                    body: $place === null
                        ? trans('notifications.push_event_result_body_unranked')
                        : trans('notifications.push_event_result_body', ['place' => Ordinal::of($place), 'total' => $total]),
                    path: "/events/{$event->id}",
                    category: NotificationCategory::EVENT_RESULT,
                    tag: 'result:'.$event->id,
                )));
            }
        }
    }

    /**
     * "Three claims are waiting for you."
     *
     * The host's half of the claim notification, and the reason the pair is
     * worth building together: without it a host who closes their tab has no
     * idea anyone is waiting, and the player who submitted proof sits there
     * for a day assuming they have been ignored.
     *
     * Counted per event and said once, with the age of the oldest — the age
     * is what turns it from a statistic into something worth acting on.
     */
    private function reviewQueues(PushNotifier $push): void
    {
        $events = Event::query()
            ->where('type', 'BINGO')
            ->whereNull('paused_at')
            ->with('bingoCard', 'authors.user')
            ->get();

        foreach ($events as $event) {
            $card = $event->bingoCard;

            if ($card === null || ! $card->requires_approval) {
                continue;
            }

            $pending = BingoCompletion::query()
                ->where('status', 'PENDING')
                ->whereIn('bingo_square_id', $card->squares()->select('id'))
                ->orderBy('created_at')
                ->get(['id', 'created_at']);

            if ($pending->isEmpty()) {
                continue;
            }

            $hosts = $event->authors->pluck('user')->filter();

            if ($hosts->isEmpty()) {
                continue;
            }

            $this->deliver("review queue for {$event->title}", fn () => $push->toUsers($hosts, new PushMessage(
                title: trans('notifications.push_review_queue_title', ['count' => $pending->count()]),
                body: trans('notifications.push_review_queue_body', [
                    'event' => $event->title,
                    'waited' => $pending->first()->created_at?->diffForHumans() ?? '',
                ]),
                path: "/events/{$event->id}",
                category: NotificationCategory::REVIEW_QUEUE,
                // Also the throttle key: one line per event per hour, however
                // many claims land inside it.
                tag: 'review:'.$event->id,
            )));
        }
    }

    /**
     * The quiet one that matters most.
     *
     * Standings rot silently: a mistyped RuneScape name, a Wise Old Man
     * outage, or a host editing the metric or the dates — after which every
     * stored row measures a window that no longer exists. The event goes on
     * showing numbers, the leaderboard goes on looking authoritative, and the
     * only person who can fix any of it is the one person with no reason to
     * suspect anything.
     */
    private function standingsHealth(PushNotifier $push): void
    {
        $events = Event::query()
            ->whereIn('type', Event::metricTypes())
            ->where(fn ($query) => $query
                ->whereNull('end_date')
                ->orWhere('end_date', '>=', Carbon::now()))
            ->with('authors.user')
            ->get();

        foreach ($events as $event) {
            $hosts = $event->authors->pluck('user')->filter();

            if ($hosts->isEmpty()) {
                continue;
            }

            // Stale beats broken when both are true: a changed measurement
            // window invalidates every row, so the individual sync errors
            // underneath it are noise until the host re-measures.
            if ($event->standingsAreStale()) {
                $this->deliver("stale standings for {$event->title}", fn () => $push->toUsers($hosts, new PushMessage(
                    title: trans('notifications.push_standings_stale_title'),
                    body: trans('notifications.push_standings_stale_body', ['event' => $event->title]),
                    path: "/events/{$event->id}",
                    category: NotificationCategory::STANDINGS_HEALTH,
                    tag: 'standings:'.$event->id,
                )));

                continue;
            }

            $broken = EventStanding::query()
                ->where('event_id', $event->id)
                ->whereNotNull('sync_error')
                ->count();

            if ($broken === 0) {
                continue;
            }

            $this->deliver("broken standings for {$event->title}", fn () => $push->toUsers($hosts, new PushMessage(
                title: trans('notifications.push_standings_error_title'),
                body: trans('notifications.push_standings_error_body', [
                    'event' => $event->title,
                    'count' => $broken,
                ]),
                path: "/events/{$event->id}",
                category: NotificationCategory::STANDINGS_HEALTH,
                tag: 'standings:'.$event->id,
            )));
        }
    }

    /**
     * "Your rolls are back."
     *
     * The chattiest thing here, hence off by default, a twelve-hour throttle,
     * and three conditions narrowing who it can reach at all:
     *
     *  - the board must have a roll limit, or nothing ever reset
     *  - they must have rolled before, so a reminder never lands on somebody
     *    who has not started playing
     *  - they must not have rolled today already
     */
    private function dailyRolls(PushNotifier $push): void
    {
        if (Carbon::now()->hour < self::ROLLS_FROM_HOUR) {
            return;
        }

        $events = Event::query()
            ->where('type', 'SNAKES_LADDERS')
            ->whereNull('paused_at')
            ->where(fn ($query) => $query->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()))
            ->with('board')
            ->get();

        foreach ($events as $event) {
            $limit = $event->board?->dice_roll_limit;

            if ($limit === null) {
                continue;
            }

            $boards = PlayerBoard::query()
                ->where('board_id', $event->board->id)
                // Never rolled means never started, and a nudge to somebody
                // who has not begun is an advert, not a reminder.
                ->whereNotNull('last_roll_date')
                ->whereDate('last_roll_date', '<', Carbon::today())
                ->with('user')
                ->get();

            foreach ($boards as $playerBoard) {
                $user = $playerBoard->user;

                if ($user === null) {
                    continue;
                }

                $this->deliver("rolls for {$user->displayName()}", fn () => $push->toUser($user, new PushMessage(
                    title: trans('notifications.push_rolls_title'),
                    body: trans('notifications.push_rolls_body', [
                        'count' => $limit,
                        'event' => $event->title,
                        'position' => $playerBoard->current_position + 1,
                    ]),
                    path: "/events/{$event->id}",
                    category: NotificationCategory::ROLLS_AVAILABLE,
                    tag: 'rolls:'.$event->id,
                )));
            }
        }
    }

    /**
     * Rank per user for a finished metric event, 1-based, ties sharing a
     * place — the same rules the standings page uses.
     *
     * @return array<string, int>
     */
    private function placings(Event $event): array
    {
        $rows = EventStanding::query()
            ->where('event_id', $event->id)
            ->whereNotNull('user_id')
            ->whereNull('sync_error')
            ->whereNotNull('synced_at')
            ->orderByDesc('gained')
            ->orderBy('username')
            ->get(['user_id', 'gained']);

        $places = [];
        $rank = 0;
        $seen = 0;
        $previous = null;

        foreach ($rows as $row) {
            $seen++;

            if ($row->gained !== $previous) {
                $rank = $seen;
                $previous = $row->gained;
            }

            $places[$row->user_id] = $rank;
        }

        return $places;
    }

    /**
     * Claim a once-per-event fact, atomically.
     *
     * `Cache::add` writes only if the key is absent and reports whether it
     * did, so two overlapping runs cannot both decide they are the one
     * announcing an event's start. A `has`-then-`put` pair looks equivalent
     * and is not.
     */
    private function claim(string $kind, Event $event, int $hours = 6): bool
    {
        // A dry run must not write the marker: doing so would make the real
        // run that follows decide the announcement had already gone out.
        if ($this->option('dry-run')) {
            return true;
        }

        return Cache::add("push-sweep:{$kind}:{$event->id}", true, $hours * 3600);
    }

    /**
     * Send, unless this is a dry run — in which case say what would have
     * gone out and send nothing.
     *
     * The check lives here rather than at each decision point because the
     * decision is exactly what a dry run is for: skipping earlier would
     * report a sweep that had not actually looked at anything.
     *
     * @param  callable(): array{sent: int, expired: int, failed: int, skipped: int}  $send
     */
    private function deliver(string $what, callable $send): void
    {
        if ($this->option('dry-run')) {
            $this->line("  would send: {$what}");

            return;
        }

        $this->totals = WebPushService::merge($this->totals, $send());
    }
}
