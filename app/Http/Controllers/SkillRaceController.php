<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\BoardAccessService;
use App\Services\EventStandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The live channel behind the skill-race leaderboard.
 *
 * **Why SSE and not WebSockets.** The data only ever flows one way: the server
 * publishes standings, the browser publishes nothing. A WebSocket (Reverb,
 * Pusher) buys a return path this feature has no use for, plus a second
 * long-running service to run and secure. `EventSource` is a browser built-in,
 * reconnects on its own, and rides the existing session cookie — so the access
 * check below is the same one the page render uses, not a parallel auth scheme.
 *
 * **What it costs.** PHP holds a worker for the life of a connection. That is
 * the real constraint, and it is why this stream is deliberately short-lived:
 * it ends after STREAM_SECONDS and lets the browser's automatic reconnect
 * start a fresh one. A capped stream frees its worker on a schedule; an
 * uncapped one leaks them until the pool is gone. Deploy accordingly — this
 * needs more PHP workers than a request/response app of the same traffic, and
 * on FrankenPHP or Octane the accounting is different again.
 *
 * **`php artisan serve` cannot serve this and anything else at once.** PHP's
 * built-in server is single-threaded; PHP_CLI_SERVER_WORKERS forks, so it
 * does nothing on Windows. One open leaderboard tab blocks the whole dev
 * server for the life of the stream. Use Herd/Valet/nginx+fpm (or Octane)
 * when working on this page — see the README.
 */
class SkillRaceController extends Controller
{
    /**
     * How long one connection lives before the client reconnects. Under the
     * 60s that proxies and PHP's own max_execution_time commonly cut at, and
     * long enough that reconnects aren't a meaningful share of the traffic.
     */
    private const STREAM_SECONDS = 45;

    /** How often the stream looks for a change. */
    private const POLL_SECONDS = 3;

    /**
     * Enter the current user into the race.
     *
     * Separate from joining the event: access says who may look, this says
     * who is competing. An OPEN event grants access without recording it, so
     * there is nothing to derive participation from even if we wanted to.
     */
    public function enter(
        Request $request,
        Event $event,
        BoardAccessService $access,
        EventStandingsService $standings,
    ): RedirectResponse {
        abort_unless($event->type === 'SKILL_RACE', 404);
        abort_unless($access->hasAccess($request->user(), $event), 403);

        try {
            if ($standings->enter($event, $request->user()) === null) {
                // Not a validation error on a field this form doesn't have —
                // the missing piece is over on the profile page, and the
                // message says so.
                return back()->with('board-save-error', trans('events.enter_needs_rsn'));
            }
        } catch (ValidationException $e) {
            // Same treatment for the same reason: a toast, because there is no
            // field on this page to hang an error message under.
            return back()->with('board-save-error', $e->errors()['osrs_username'][0]);
        }

        return back()->with('board-save', trans('events.entered'));
    }

    public function leave(
        Request $request,
        Event $event,
        EventStandingsService $standings,
    ): RedirectResponse {
        abort_unless($event->type === 'SKILL_RACE', 404);

        $standings->leave($event, $request->user());

        return back()->with('board-save', trans('events.left'));
    }

    public function stream(
        Request $request,
        Event $event,
        BoardAccessService $access,
        EventStandingsService $standings,
    ): StreamedResponse {
        abort_unless($event->type === 'SKILL_RACE', 404);
        abort_unless($access->hasAccess($request->user(), $event), 403);

        // EventSource resends the id of the last event it received, so a
        // reconnect can skip re-sending standings the browser already has.
        // Absent on a first connection, which is what makes that send happen.
        $lastSeen = $request->header('Last-Event-ID');

        // Written now rather than when the stream ends 45 seconds from now.
        // The session is otherwise saved on terminate, and a snapshot that
        // old would overwrite whatever a request from another tab wrote in
        // the meantime. Nothing below touches the session, so an early save
        // costs nothing.
        $request->session()->save();

        $response = new StreamedResponse(function () use ($event, $standings, $lastSeen) {
            // max_execution_time is 30 seconds by default under both php-fpm
            // and the CLI server, so a 45-second stream is killed mid-flight
            // by a fatal error — which then can't even be reported, because
            // the headers went out long ago. The margin is for the work
            // around the sleep, not extra streaming.
            set_time_limit(self::STREAM_SECONDS + 15);

            // Keep running long enough to notice the disconnect and stop; the
            // loop below checks connection_aborted() itself.
            ignore_user_abort(true);

            $deadline = Carbon::now()->addSeconds(self::STREAM_SECONDS);

            $lastFingerprint = $standings->fingerprint($event);

            if ($lastFingerprint !== $lastSeen) {
                $this->send($standings->forEvent($event)->all(), $lastFingerprint);
            }

            while (Carbon::now()->lessThan($deadline)) {
                // Bail the moment the browser goes away — a closed tab
                // otherwise keeps this worker busy until the deadline.
                if (connection_aborted()) {
                    return;
                }

                sleep(self::POLL_SECONDS);

                $current = $standings->fingerprint($event);

                if ($current === $lastFingerprint) {
                    // A comment line, not an event. Keeps proxies from
                    // treating an idle connection as dead without the client
                    // having to handle a no-op message.
                    echo ": keep-alive\n\n";
                    $this->flush();

                    continue;
                }

                $lastFingerprint = $current;
                $this->send($standings->forEvent($event)->all(), $current);
            }

            // Tell the client how soon to come back. EventSource reconnects on
            // its own when the stream ends; this just sets the delay.
            echo "retry: 2000\n\n";
            $this->flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        // nginx buffers proxied responses by default, which holds every event
        // until the connection closes — turning a live stream into one big
        // delivery at the end.
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /** @param  array<int, mixed>  $standings */
    private function send(array $standings, string $id): void
    {
        echo 'id: '.$id."\n";
        echo "event: standings\n";
        echo 'data: '.json_encode(['standings' => $standings])."\n\n";

        $this->flush();
    }

    /**
     * Push whatever is buffered out to the client immediately.
     *
     * ob_flush() throws a notice when no output buffer is active, and whether
     * one is depends on output_buffering in php.ini — so the level is checked
     * rather than assumed.
     */
    private function flush(): void
    {
        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }
}
