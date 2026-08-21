<?php

namespace App\Http\Controllers;

use App\Events\Channels\EventChannelResolver;
use App\Models\Event;
use App\Services\BoardAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One live channel per event, whatever kind of event it is.
 *
 * This replaces the skill-race-only stream. It knows nothing about standings
 * or bingo cards: it resolves a channel for the event's type and polls it.
 * Adding an event type means writing a channel, not touching this file.
 *
 * **Why SSE and not WebSockets.** The data only ever flows one way: the server
 * publishes, the browser publishes nothing. A WebSocket (Reverb, Pusher) buys
 * a return path this has no use for, plus a second long-running service to run
 * and secure. `EventSource` is a browser built-in, reconnects on its own, and
 * rides the existing session cookie — so the access check below is the same
 * one the page render uses, not a parallel auth scheme.
 *
 * **What it costs.** PHP holds a worker for the life of a connection. That is
 * the real constraint, and why this stream is deliberately short-lived: it
 * ends after STREAM_SECONDS and lets the browser's automatic reconnect start a
 * fresh one. A capped stream frees its worker on a schedule; an uncapped one
 * leaks them until the pool is gone. Deploy accordingly — this needs more PHP
 * workers than a request/response app of the same traffic.
 *
 * **`php artisan serve` cannot serve this and anything else at once.** PHP's
 * built-in server is single-threaded; PHP_CLI_SERVER_WORKERS forks, so it does
 * nothing on Windows. Use Herd/Valet/nginx+fpm when working on these pages.
 */
class EventStreamController extends Controller
{
    /**
     * How long one connection lives before the client reconnects. Under the
     * 60s that proxies and PHP's own max_execution_time commonly cut at, and
     * long enough that reconnects aren't a meaningful share of the traffic.
     */
    private const STREAM_SECONDS = 45;

    /** How often the stream looks for a change. */
    private const POLL_SECONDS = 3;

    public function __invoke(
        Request $request,
        Event $event,
        BoardAccessService $access,
        EventChannelResolver $resolver,
    ): StreamedResponse {
        $channel = $resolver->for($event);

        // A type with nothing to stream gets a 404 rather than an idle
        // connection — holding a worker open for an event that can never push
        // anything is pure cost.
        abort_if($channel === null, 404);
        abort_unless($access->hasAccess($request->user(), $event), 403);

        // EventSource resends the id of the last event it received, so a
        // reconnect can skip re-sending state the browser already has. Absent
        // on a first connection, which is what makes that send happen.
        $lastSeen = $request->header('Last-Event-ID');

        // Written now rather than when the stream ends 45 seconds from now.
        // The session is otherwise saved on terminate, and a snapshot that old
        // would overwrite whatever a request from another tab wrote in the
        // meantime. Nothing below touches the session.
        $request->session()->save();

        $response = new StreamedResponse(function () use ($event, $channel, $lastSeen) {
            // max_execution_time is 30 seconds by default under both php-fpm
            // and the CLI server, so a 45-second stream is killed mid-flight
            // by a fatal error — which then can't even be reported, because
            // the headers went out long ago.
            set_time_limit(self::STREAM_SECONDS + 15);
            ignore_user_abort(true);

            $deadline = Carbon::now()->addSeconds(self::STREAM_SECONDS);
            $last = $channel->fingerprint($event);

            if ($last !== $lastSeen) {
                $this->send($channel->name(), $channel->payload($event), $last);
            }

            while (Carbon::now()->lessThan($deadline)) {
                // Bail the moment the browser goes away — a closed tab
                // otherwise keeps this worker busy until the deadline.
                if (connection_aborted()) {
                    return;
                }

                sleep(self::POLL_SECONDS);

                $current = $channel->fingerprint($event);

                if ($current === $last) {
                    // A comment line, not an event. Keeps proxies from
                    // treating an idle connection as dead without the client
                    // having to handle a no-op message.
                    echo ": keep-alive\n\n";
                    $this->flush();

                    continue;
                }

                $last = $current;
                $this->send($channel->name(), $channel->payload($event), $current);
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

    private function send(string $name, array $payload, string $id): void
    {
        echo 'id: '.$id."\n";
        echo 'event: '.$name."\n";
        echo 'data: '.json_encode($payload)."\n\n";

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
