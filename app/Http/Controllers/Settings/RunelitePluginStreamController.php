<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\RunelitePluginService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The RuneLite plugin's live status, for one signed-in account only.
 *
 * Mirrors EventStreamController's mechanics (cheap fingerprint, payload only
 * on change, ~45s cap) but cannot be an EventChannel: this data is per-user,
 * where every EventChannel is a public stream shared by every viewer.
 */
class RunelitePluginStreamController extends Controller
{
    private const STREAM_SECONDS = 45;

    private const POLL_SECONDS = 3;

    public function __invoke(Request $request, RunelitePluginService $plugin): StreamedResponse
    {
        abort_if(Setting::get('runelite_plugin_mode') === 'off', 404);

        $user = $request->user();
        $lastSeen = $request->header('Last-Event-ID');

        $request->session()->save();

        $response = new StreamedResponse(function () use ($user, $plugin, $lastSeen) {
            set_time_limit(self::STREAM_SECONDS + 15);
            ignore_user_abort(true);

            $deadline = Carbon::now()->addSeconds(self::STREAM_SECONDS);
            $last = $plugin->statusFingerprint($user);

            if ($last !== $lastSeen) {
                $this->send($plugin->status($user), $last);
            }

            while (Carbon::now()->lessThan($deadline)) {
                if (connection_aborted()) {
                    return;
                }

                sleep(self::POLL_SECONDS);

                $current = $plugin->statusFingerprint($user);

                if ($current === $last) {
                    echo ": keep-alive\n\n";
                    $this->flush();

                    continue;
                }

                $last = $current;
                $this->send($plugin->status($user), $current);
            }

            echo "retry: 2000\n\n";
            $this->flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    private function send(array $payload, string $id): void
    {
        echo 'id: '.$id."\n";
        echo 'event: status'."\n";
        echo 'data: '.json_encode($payload)."\n\n";

        $this->flush();
    }

    private function flush(): void
    {
        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }
}
