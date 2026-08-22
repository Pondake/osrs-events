import { transChoice } from 'laravel-vue-i18n';

/**
 * How long a new event is pre-filled to run for.
 *
 * Written as a short form — `10d`, `2w`, `1m` — because that is how people say
 * it, and a bare number is read as days so an existing "14" keeps working.
 *
 * The unit is kept rather than converted, because a month has to mean a
 * calendar month: an event starting on 31 January and running "1m" should end
 * on 28 February. Flattening that to 30 days throws away the only thing that
 * makes the answer right.
 *
 * Mirrors app/Support/EventDuration.php. Both are tested against the same
 * awkward dates on purpose — two implementations of a calendar in two
 * languages is exactly where they drift apart.
 */

export const DEFAULT_DURATION = '2w';

const UNITS = { d: 'days', w: 'weeks', m: 'months' };

const CEILING = { days: 365, weeks: 52, months: 12 };

/** @returns {{count: number, unit: string}|null} */
export function parseDuration(spec) {
    const match = String(spec ?? '').trim().toLowerCase().match(/^(\d{1,3})\s*([dwm]?)$/);

    if (!match) return null;

    const count = Number(match[1]);

    if (count < 1) return null;

    return { count, unit: UNITS[match[2] || 'd'] };
}

export function isValidDuration(spec) {
    const parsed = parseDuration(spec);

    return parsed !== null && parsed.count <= CEILING[parsed.unit];
}

/**
 * The end date this duration gives, counted from a start.
 *
 * Months are clamped to the end of a shorter month rather than rolling into
 * the next one — JavaScript's own `setMonth` does the opposite, turning
 * 31 January plus a month into 3 March, which is not what anybody means.
 */
export function addDuration(start, spec) {
    const parsed = parseDuration(spec) ?? parseDuration(DEFAULT_DURATION);
    const end = new Date(start.getTime());

    if (parsed.unit === 'days') {
        end.setDate(end.getDate() + parsed.count);

        return end;
    }

    if (parsed.unit === 'weeks') {
        end.setDate(end.getDate() + parsed.count * 7);

        return end;
    }

    const day = end.getDate();

    // To the first of the month, move, then put the day back — capped at
    // however many days that month actually has.
    end.setDate(1);
    end.setMonth(end.getMonth() + parsed.count);

    const lastDayOfTarget = new Date(end.getFullYear(), end.getMonth() + 1, 0).getDate();

    end.setDate(Math.min(day, lastDayOfTarget));

    return end;
}

/**
 * "2 weeks", for the hint under the admin field.
 *
 * transChoice, not trans: "1 months" is the kind of small wrongness that
 * makes a field look unfinished, and the singular is the common case for a
 * month.
 */
export function describeDuration(spec) {
    const parsed = parseDuration(spec);

    if (parsed === null) return null;

    return transChoice(`duration.${parsed.unit}`, parsed.count, { count: parsed.count });
}
