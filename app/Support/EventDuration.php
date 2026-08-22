<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * How long a new event is pre-filled to run for.
 *
 * Written as a short form — `10d`, `2w`, `1m` — because that is how people
 * say it, and a bare number is read as days so an existing "14" keeps working.
 *
 * **The unit is stored, not converted to days.** A month has to mean a
 * calendar month: an event starting on 31 January and running "1m" should end
 * on 28 February, not on 2 March. Flattening that to 30 days at save time
 * throws away the only thing that makes the answer right, and it is wrong by
 * up to three days depending on which month somebody happens to be in.
 *
 * Mirrored in resources/js/Support/duration.js, which computes the same end
 * date in the create form before anything is submitted. Both are tested
 * against the same awkward dates on purpose — two implementations of a
 * calendar in two languages is exactly where they drift.
 */
final class EventDuration
{
    /** What a value with no unit on it means. */
    public const DEFAULT = '2w';

    private const UNITS = ['d' => 'days', 'w' => 'weeks', 'm' => 'months'];

    /**
     * @return array{count: int, unit: string}|null null when it is not a
     *                                              duration at all
     */
    public static function parse(?string $spec): ?array
    {
        $spec = strtolower(trim((string) $spec));

        if ($spec === '' || ! preg_match('/^(\d{1,3})\s*([dwm]?)$/', $spec, $matches)) {
            return null;
        }

        $count = (int) $matches[1];

        if ($count < 1) {
            return null;
        }

        // A bare number is days, which is what the setting held before short
        // forms existed.
        $unit = self::UNITS[$matches[2] ?: 'd'];

        return ['count' => $count, 'unit' => $unit];
    }

    public static function isValid(?string $spec): bool
    {
        $parsed = self::parse($spec);

        if ($parsed === null) {
            return false;
        }

        // A ceiling in the same shape as the old integer rule's max:365 —
        // long enough for anything a clan runs, short enough that a typo
        // cannot pre-fill an event ending in the next century.
        return match ($parsed['unit']) {
            'days' => $parsed['count'] <= 365,
            'weeks' => $parsed['count'] <= 52,
            default => $parsed['count'] <= 12,
        };
    }

    /**
     * The end date this duration gives, counted from a start.
     *
     * Carbon clamps a month addition to the end of the shorter month, which
     * is the behaviour that makes "1m" from 31 January land on 28 February
     * rather than spilling into March.
     */
    public static function endFrom(CarbonInterface $start, ?string $spec): CarbonInterface
    {
        $parsed = self::parse($spec) ?? self::parse(self::DEFAULT);

        return Carbon::instance($start->toDateTime())
            ->settings(['monthOverflow' => false])
            ->add($parsed['unit'], $parsed['count']);
    }
}
