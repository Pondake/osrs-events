import { describe, expect, it } from 'vitest';

import { addDuration, describeDuration, isValidDuration, parseDuration } from '@/Support/duration';

/**
 * How long a new event is pre-filled to run for.
 *
 * This file exists twice — here and in tests/Feature/EventDurationTest.php —
 * against the same awkward dates on purpose. The create form computes the end
 * date in the browser before anything is submitted, and the server computes
 * it from the same setting; two implementations of a calendar in two
 * languages is exactly where they drift, and the drift is invisible until
 * somebody's event ends on the wrong day.
 */

/** JS months are zero-based, which is its own small trap. */
const on = (year, month, day) => new Date(year, month - 1, day);
const iso = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

describe('parseDuration', () => {
    it('reads the short forms', () => {
        expect(parseDuration('10d')).toEqual({ count: 10, unit: 'days' });
        expect(parseDuration('2w')).toEqual({ count: 2, unit: 'weeks' });
        expect(parseDuration('1m')).toEqual({ count: 1, unit: 'months' });
    });

    /** The setting held a plain integer before short forms existed. */
    it('reads a bare number as days', () => {
        expect(parseDuration('14')).toEqual({ count: 14, unit: 'days' });
    });

    it('is forgiving about case and spacing', () => {
        expect(parseDuration('  2W ')).toEqual({ count: 2, unit: 'weeks' });
    });

    it('refuses what is not a duration', () => {
        for (const spec of ['', null, undefined, 'soon', '2y', '-3d', '0d', '2 weeks', '1.5m']) {
            expect(parseDuration(spec), String(spec)).toBeNull();
        }
    });
});

describe('isValidDuration', () => {
    /**
     * A ceiling per unit rather than one number: 52 weeks and 12 months are
     * both about a year, and 365 weeks is a typo nobody notices until the
     * standings never close.
     */
    it('caps each unit at about a year', () => {
        expect(isValidDuration('365d')).toBe(true);
        expect(isValidDuration('366d')).toBe(false);
        expect(isValidDuration('52w')).toBe(true);
        expect(isValidDuration('53w')).toBe(false);
        expect(isValidDuration('12m')).toBe(true);
        expect(isValidDuration('13m')).toBe(false);
    });
});

describe('addDuration', () => {
    it('adds days and weeks plainly', () => {
        expect(iso(addDuration(on(2026, 3, 1), '10d'))).toBe('2026-03-11');
        expect(iso(addDuration(on(2026, 3, 1), '2w'))).toBe('2026-03-15');
    });

    /** The whole reason the unit is stored rather than converted. */
    it('treats a month as a calendar month', () => {
        expect(iso(addDuration(on(2026, 1, 1), '1m'))).toBe('2026-02-01');
        expect(iso(addDuration(on(2026, 2, 1), '1m'))).toBe('2026-03-01');
    });

    /**
     * The case JavaScript gets wrong on its own: `setMonth` on 31 January
     * rolls into 3 March, because there is no 31 February to land on. Nobody
     * means that by "a month".
     */
    it('clamps to the end of a shorter month instead of rolling over', () => {
        expect(iso(addDuration(on(2026, 1, 31), '1m'))).toBe('2026-02-28');
        expect(iso(addDuration(on(2028, 1, 31), '1m'))).toBe('2028-02-29');
        expect(iso(addDuration(on(2026, 5, 31), '1m'))).toBe('2026-06-30');
    });

    it('crosses a year end', () => {
        expect(iso(addDuration(on(2026, 12, 15), '1m'))).toBe('2027-01-15');
        expect(iso(addDuration(on(2026, 12, 20), '2w'))).toBe('2027-01-03');
    });

    /** A broken setting must still produce a usable form, not an error. */
    it('falls back to the default rather than throwing', () => {
        expect(iso(addDuration(on(2026, 3, 1), 'nonsense'))).toBe(iso(addDuration(on(2026, 3, 1), '2w')));
        expect(iso(addDuration(on(2026, 3, 1), null))).toBe(iso(addDuration(on(2026, 3, 1), '2w')));
    });

    /** The date handed in is not the one that comes back changed. */
    it('does not mutate the start it was given', () => {
        const start = on(2026, 3, 1);

        addDuration(start, '1m');

        expect(iso(start)).toBe('2026-03-01');
    });
});

describe('describeDuration', () => {
    it('says the short form back in words', () => {
        expect(describeDuration('1m')).toBe('t:duration.months:1');
        expect(describeDuration('10d')).toBe('t:duration.days:10');
    });

    /**
     * The count is passed through so the translation can pick a form. "1
     * months" is the kind of small wrongness that makes a field look
     * unfinished, and a month is usually singular.
     */
    it('carries the count so one month is not one months', () => {
        expect(describeDuration('1m')).toBe('t:duration.months:1');
        expect(describeDuration('3m')).toBe('t:duration.months:3');
    });

    it('says nothing about something it cannot read', () => {
        expect(describeDuration('2y')).toBeNull();
    });
});
