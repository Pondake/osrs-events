<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * When each scheduled command last finished.
 *
 * Laravel records nothing about this by default, which makes a dead scheduler
 * the quietest failure in the whole app: standings stop moving, five of the
 * nine notification categories stop firing, audit rows stop being pruned —
 * and every page still renders perfectly. Nothing anywhere says the cron
 * entry is missing.
 *
 * Recorded from `routes/console.php` via `->onSuccess()`, so it measures what
 * actually completed rather than what was scheduled. **Absence is the signal
 * this exists for**: a stamp that was never written and a stamp two days old
 * both mean the same thing, and both are invisible without asking.
 *
 * Cache rather than a settings row: it is operational state with no meaning
 * to the application itself, and it is rewritten every few minutes.
 */
final class ScheduleHeartbeat
{
    /**
     * Kept far longer than any interval it measures. A one-hour TTL would
     * expire the evidence of the outage while the outage was still going,
     * turning "last ran two days ago" into "never ran" — the same display
     * as a fresh install, which is the one thing it must not look like.
     */
    private const TTL_DAYS = 30;

    public static function record(string $command): void
    {
        Cache::put(self::key($command), Carbon::now()->toIso8601String(), self::TTL_DAYS * 86400);
    }

    public static function lastRun(string $command): ?Carbon
    {
        $stamp = Cache::get(self::key($command));

        return $stamp === null ? null : Carbon::parse($stamp);
    }

    private static function key(string $command): string
    {
        return 'schedule-heartbeat:'.$command;
    }
}
