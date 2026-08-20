<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for the Wise Old Man API — https://wiseoldman.net.
 *
 * Wise Old Man is the open-source OSRS progress tracker the community already
 * runs its skill competitions on. This app does not re-implement hiscores
 * tracking: it reads gains from theirs, and the whole skill-race feature is
 * modelled on their competition view. Credit to the Wise Old Man team; their
 * API docs are at https://docs.wiseoldman.net.
 *
 * Two deliberate constraints shape everything below:
 *
 * - **They ask for a User-Agent that identifies the caller.** An anonymous
 *   client is asking to be blocked, and rightly. WOM_USER_AGENT carries a
 *   contact address so they can reach whoever is hammering them.
 *
 * - **20 requests per 60 seconds** without an API key (100 with one). That is
 *   a per-participant lookup budget of one every three seconds, which is why
 *   the sync is a throttled command rather than something a page request does.
 *
 * This class does no throttling itself — it is a thin transport, and the
 * caller owns the pacing. See SyncEventStandings, which is the only caller.
 */
class WiseOldManService
{
    public const BASE_URL = 'https://api.wiseoldman.net/v2';

    /** Their published anonymous limit; the sync paces itself against this. */
    public const RATE_LIMIT_PER_MINUTE = 20;

    private function client(): PendingRequest
    {
        $request = Http::baseUrl(self::BASE_URL)
            ->timeout(15)
            ->connectTimeout(8)
            ->withHeaders([
                'User-Agent' => config('services.wom.user_agent'),
                'Accept' => 'application/json',
            ]);

        // Optional. Raises the rate limit to 100/min, which only matters once
        // an event has enough participants that 20/min is a real ceiling.
        if ($key = config('services.wom.api_key')) {
            $request = $request->withHeaders(['x-api-key' => $key]);
        }

        return $request;
    }

    /**
     * XP in one skill gained between two moments.
     *
     * Returns Wise Old Man's own delta shape unchanged — ['gained', 'start',
     * 'end'] — because reshaping it here is how the two drift apart. Null
     * means "no answer", not "no gains": an unknown player, a window with no
     * snapshots in it, or a request that failed. The caller stores that
     * distinction rather than flattening it to zero, so the UI can say "not
     * tracked yet" instead of implying someone gained nothing.
     *
     * @return array{gained:int,start:int,end:int}|null
     */
    public function gainedXp(string $username, string $metric, CarbonInterface $start, CarbonInterface $end): ?array
    {
        $response = $this->client()->get('/players/'.rawurlencode($username).'/gained', [
            // ISO-8601 in UTC — their API rejects a bare date.
            'startDate' => $start->toIso8601ZuluString('milliseconds'),
            'endDate' => $end->toIso8601ZuluString('milliseconds'),
        ]);

        if ($response->status() === 404) {
            // Not an error worth logging: it just means nobody has ever
            // tracked this name. The caller turns it into a message telling
            // the participant to look themselves up once.
            return null;
        }

        if (! $response->successful()) {
            Log::warning('Wise Old Man gains lookup failed', [
                'username' => $username,
                'status' => $response->status(),
            ]);

            return null;
        }

        $experience = $response->json("data.skills.{$metric}.experience");

        if (! is_array($experience) || ! isset($experience['gained'])) {
            return null;
        }

        return [
            // A negative gain is possible on their side (a rollback, a
            // de-ironing, a hiscores glitch). Clamped, because a leaderboard
            // that can go below zero is a bug report waiting to happen.
            'gained' => max(0, (int) $experience['gained']),
            'start' => (int) ($experience['start'] ?? 0),
            'end' => (int) ($experience['end'] ?? 0),
        ];
    }

    /**
     * Ask Wise Old Man to re-import a player from the OSRS hiscores.
     *
     * This is a WRITE against a third-party service — it creates a new
     * snapshot on their side, which is what their site's own "Update" button
     * does. Nothing calls it automatically: the sync command only does so
     * behind an explicit --track flag, so an operator opts into generating
     * that traffic rather than the app doing it on every page view.
     *
     * Without at least one snapshot inside the event window, gainedXp()
     * returns null forever — which is the entire reason this exists.
     */
    public function trackPlayer(string $username): bool
    {
        $response = $this->client()->post('/players/'.rawurlencode($username));

        if (! $response->successful()) {
            Log::warning('Wise Old Man player update failed', [
                'username' => $username,
                'status' => $response->status(),
            ]);
        }

        return $response->successful();
    }
}
