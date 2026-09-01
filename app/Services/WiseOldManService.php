<?php

namespace App\Services;

use App\Exceptions\WiseOldManRateLimited;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
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

    /** With an API key they allow five times as much. */
    public const RATE_LIMIT_PER_MINUTE_WITH_KEY = 100;

    /**
     * How many requests a minute this deployment may make.
     *
     * Read rather than assumed constant: pacing every install at the
     * anonymous rate means an operator who went and got a key still waits
     * three seconds a participant for no reason.
     *
     * One key for the whole deployment, and Wise Old Man asked for it that
     * way (2026-08-31): per-user keys would mean this site collecting other
     * people's credentials, and one backend key is also how they can see
     * what this site is doing request-wise. See the backlog entry.
     */
    public function requestsPerMinute(): int
    {
        return config('services.wom.api_key')
            ? self::RATE_LIMIT_PER_MINUTE_WITH_KEY
            : self::RATE_LIMIT_PER_MINUTE;
    }

    /**
     * Whether to pace outbound requests at all.
     *
     * Off in tests, where the API is faked: sleeping is politeness toward a
     * live service, and there isn't one. Never turn this off against the real
     * API — the limit is theirs, not ours.
     */
    public function shouldThrottle(): bool
    {
        return (bool) config('services.wom.throttle', true);
    }

    /**
     * Does Wise Old Man know this account?
     *
     * Three answers, not two, and the difference matters: **true** found,
     * **false** genuinely unknown to them (a real 404 —
     * `{"code":"PLAYER_NOT_FOUND"}`, verified against their API, not an empty
     * body), and **null** meaning we could not find out. A timeout, a 500 or
     * a rate-limit rejection must never be reported to a user as "that
     * account doesn't exist"; being wrong in that direction tells someone
     * their own RSN is a typo when it isn't.
     *
     * `displayName` comes back canonically cased ("Pondake", not the
     * lowercase key), which is worth storing over whatever the user typed.
     *
     * Short timeout on purpose: this runs inline on signup, and a slow
     * third-party lookup must not become a slow registration. Failing to a
     * null answer is cheap; making someone wait is not.
     *
     * @return array{found: bool|null, displayName: string|null}
     */
    public function findPlayer(string $username): array
    {
        try {
            $response = $this->client(timeout: 6)->get('/players/'.rawurlencode($username));
        } catch (ConnectionException $e) {
            Log::warning('Wise Old Man lookup unreachable', ['username' => $username, 'error' => $e->getMessage()]);

            return ['found' => null, 'displayName' => null];
        }

        if ($response->status() === 404) {
            return ['found' => false, 'displayName' => null];
        }

        if (! $response->successful()) {
            Log::warning('Wise Old Man lookup failed', ['username' => $username, 'status' => $response->status()]);

            return ['found' => null, 'displayName' => null];
        }

        return [
            'found' => true,
            'displayName' => $response->json('displayName') ?: null,
        ];
    }

    private function client(int $timeout = 15): PendingRequest
    {
        $request = Http::baseUrl(self::BASE_URL)
            ->timeout($timeout)
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
     * How much of one metric a player gained between two moments.
     *
     * Handles both kinds this app races on, because their API returns them in
     * one envelope and they differ only in where the number sits:
     *
     *   skills → `data.skills.{metric}.experience.{gained,start,end}`
     *   bosses → `data.bosses.{metric}.kills.{gained,start,end}`
     *
     * Returns Wise Old Man's own delta shape unchanged — ['gained', 'start',
     * 'end'] — because reshaping it here is how the two drift apart. Null
     * means "no answer", not "no gains": an unknown player, a window with no
     * snapshots in it, or a request that failed. The caller stores that
     * distinction rather than flattening it to zero, so the UI can say "not
     * tracked yet" instead of implying someone gained nothing.
     *
     * @param  string  $kind  'skill' or 'boss'
     * @return array{gained:int,start:int|null,end:int|null}|null
     */
    public function gained(string $username, string $metric, string $kind, CarbonInterface $start, CarbonInterface $end): ?array
    {
        // Where to look, and what the number is called once you are there.
        [$group, $field] = match ($kind) {
            'boss' => ['bosses', 'kills'],
            default => ['skills', 'experience'],
        };

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

        // Separated from the failure below on purpose. Falling through to
        // `return null` here made the caller write `sync_error =
        // 'not_tracked'`, whose message sends the player to wiseoldman.net to
        // look up a name that is already tracked perfectly well — wrong
        // advice, and it blames them for our pacing. Thrown rather than
        // returned so it cannot be mistaken for an answer.
        if ($response->status() === 429) {
            Log::warning('Wise Old Man rate limit reached', ['username' => $username]);

            throw new WiseOldManRateLimited($username);
        }

        if (! $response->successful()) {
            Log::warning('Wise Old Man gains lookup failed', [
                'username' => $username,
                'status' => $response->status(),
            ]);

            return null;
        }

        $delta = $response->json("data.{$group}.{$metric}.{$field}");

        if (! is_array($delta) || ! isset($delta['gained'])) {
            return null;
        }

        return [
            // A negative gain is possible on their side (a rollback, a
            // de-ironing, a hiscores glitch). Clamped, because a leaderboard
            // that can go below zero is a bug report waiting to happen.
            'gained' => max(0, (int) $delta['gained']),
            // -1 is their sentinel for "unranked" — the player is not on the
            // hiscores for this metric at all, which is common for bosses
            // nobody in the race has killed. It is an absence, not a count,
            // so it is stored as null rather than written into an unsigned
            // column as a negative number.
            'start' => self::value($delta['start'] ?? null),
            'end' => self::value($delta['end'] ?? null),
        ];
    }

    /** Their -1 "unranked" sentinel becomes null; anything else is a count. */
    private static function value(mixed $raw): ?int
    {
        return $raw === null || (int) $raw < 0 ? null : (int) $raw;
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
     * Without at least one snapshot inside the event window, gained()
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
