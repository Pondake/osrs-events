import { describe, expect, it, vi } from 'vitest';
import { trans } from 'laravel-vue-i18n';

import { BOARD_TILE_COUNT, boardEventStatus, eventStatus, formatBoardSize, formatDate } from '@/Support/board';
import { eventTypeMeta } from '@/Support/eventTypes';
import { metricIconUrl, metricKindFor, metricLabel, rankedByLabel } from '@/Support/metrics';

/**
 * The pure helpers under resources/js/Support.
 *
 * These are shared by pages that render the same fact in different places —
 * a status badge on the hub and on the detail page, a metric name in the
 * standings and in the picker — which is exactly where two implementations
 * quietly drift apart. That has already happened once here (the status label
 * read "Live" on one page and "Running" on the next), so the rules are worth
 * pinning down rather than eyeballing.
 */
describe('boardEventStatus', () => {
    const now = new Date('2026-08-22T12:00:00Z');

    it('is live between the dates', () => {
        expect(boardEventStatus('2026-08-01', '2026-09-01', now)).toBe('live');
    });

    it('is upcoming before the start', () => {
        expect(boardEventStatus('2026-09-01', '2026-09-30', now)).toBe('upcoming');
    });

    it('is ended after the end', () => {
        expect(boardEventStatus('2026-07-01', '2026-08-01', now)).toBe('ended');
    });

    /**
     * Compared as UTC days, not instants — SSR renders in the server's
     * timezone and the browser re-renders in the visitor's, and an event
     * that reads "live" on the server and "ended" in the browser is a
     * hydration mismatch nobody can reproduce on their own machine.
     */
    it('counts the whole of the last day as live', () => {
        expect(boardEventStatus('2026-08-01', '2026-08-22', new Date('2026-08-22T23:59:00Z'))).toBe('live');
        expect(boardEventStatus('2026-08-01', '2026-08-22', new Date('2026-08-23T00:01:00Z'))).toBe('ended');
    });

    it('counts the first day as live rather than upcoming', () => {
        expect(boardEventStatus('2026-08-22', '2026-09-01', new Date('2026-08-22T00:01:00Z'))).toBe('live');
    });

    /** An event with no window at all is running, not broken. */
    it('treats missing dates as live', () => {
        expect(boardEventStatus(null, null, now)).toBe('live');
        expect(boardEventStatus('2026-08-01', null, now)).toBe('live');
        expect(boardEventStatus(null, '2026-09-01', now)).toBe('live');
    });

    /** The API sends full timestamps, not bare dates. */
    it('accepts an ISO timestamp as well as a date', () => {
        expect(boardEventStatus('2026-08-01T00:00:00.000000Z', '2026-09-01T00:00:00.000000Z', now)).toBe('live');
    });
});

describe('eventStatus', () => {
    const now = new Date('2026-08-23T12:00:00Z');
    const running = { start_date: '2026-08-01', end_date: '2026-09-01' };

    it('reports a paused event as paused rather than live', () => {
        expect(eventStatus({ ...running, paused_at: '2026-08-23T09:00:00Z' }, now)).toBe('paused');
    });

    it('leaves an un-paused event to its dates', () => {
        expect(eventStatus({ ...running, paused_at: null }, now)).toBe('live');
        expect(eventStatus({ start_date: '2026-09-05', end_date: '2026-09-10' }, now)).toBe('upcoming');
    });

    // Somebody has to come back and start a paused event; nobody comes back
    // to a finished one, so 'ended' is the truer word for it.
    it('prefers ended over paused once the end date has gone by', () => {
        expect(eventStatus({ start_date: '2026-07-01', end_date: '2026-08-01', paused_at: '2026-07-20T09:00:00Z' }, now)).toBe('ended');
    });
});

describe('board sizes', () => {
    it('labels every size it knows', () => {
        expect(formatBoardSize('SIZE_5X5')).toBe('5×5');
        expect(formatBoardSize('SIZE_9X9')).toBe('9×9');
    });

    /** A size added server-side before the map catches up shows the raw
     *  value rather than "undefined". */
    it('falls back to the raw value', () => {
        expect(formatBoardSize('SIZE_11X11')).toBe('SIZE_11X11');
    });

    it('counts tiles as the square of the side', () => {
        expect(BOARD_TILE_COUNT.SIZE_5X5).toBe(25);
        expect(BOARD_TILE_COUNT.SIZE_7X7).toBe(49);
        expect(BOARD_TILE_COUNT.SIZE_9X9).toBe(81);
    });
});

describe('formatDate', () => {
    it('renders an em dash for nothing', () => {
        expect(formatDate(null)).toBe('—');
        expect(formatDate('')).toBe('—');
    });

    it('renders a readable day', () => {
        expect(formatDate('2026-08-22T00:00:00.000000Z')).toContain('2026');
    });
});

describe('metricKindFor', () => {
    it('knows which vocabulary each racing type uses', () => {
        expect(metricKindFor('SKILL_RACE')).toBe('skill');
        expect(metricKindFor('DROP_RACE')).toBe('boss');
    });

    /** Snakes & Ladders and bingo race on nothing. */
    it('is null for a type with no metric', () => {
        expect(metricKindFor('SNAKES_LADDERS')).toBeNull();
        expect(metricKindFor('BINGO')).toBeNull();
        expect(metricKindFor(undefined)).toBeNull();
    });
});

describe('metricLabel', () => {
    /**
     * The namespace has to match the kind. A boss slug looked up under
     * `skills.` renders as the raw key, which is how "abyssal_sire" ends up
     * printed on a page.
     */
    it('looks a metric up in its own namespace', () => {
        expect(metricLabel('mining', 'skill')).toBe('t:skills.mining');
        expect(metricLabel('zulrah', 'boss')).toBe('t:bosses.zulrah');
    });

    it('renders an em dash for no metric', () => {
        expect(metricLabel(null, 'skill')).toBe('—');
    });

    /**
     * Wise Old Man can add a metric before this app has a name for it. The
     * helper spots that trans() handed the key straight back and prints the
     * slug — readable — rather than "skills.some_new_boss".
     */
    it('falls back to the slug when there is no translation', () => {
        // What laravel-vue-i18n actually does for a key it cannot find: hand
        // the key straight back. metricLabel spots that and prints the slug.
        vi.mocked(trans).mockImplementationOnce((key) => key);

        expect(metricLabel('a_brand_new_boss', 'boss')).toBe('a_brand_new_boss');
    });
});

describe('rankedByLabel', () => {
    /** A boss race counts kills; calling those XP would just be wrong. */
    it('counts kills for a boss and XP for a skill', () => {
        expect(rankedByLabel('zulrah', 'boss')).toContain('ranked_by_kills');
        expect(rankedByLabel('mining', 'skill')).toContain('ranked_by');
    });
});

describe('metricIconUrl', () => {
    it('points at the committed skill icon', () => {
        expect(metricIconUrl('mining', 'skill')).toBe('/images/osrs/skills/mining.png');
    });

    /**
     * Bosses have none — the icon set is built from wiki item and category
     * images and there is no "Zulrah icon". Better no icon than the wrong
     * one; see scripts/extract-osrs-icons.mjs.
     */
    it('has nothing for a boss', () => {
        expect(metricIconUrl('zulrah', 'boss')).toBeNull();
    });

    it('has nothing for no metric', () => {
        expect(metricIconUrl(null, 'skill')).toBeNull();
    });
});

describe('eventTypeMeta', () => {
    it('describes every type the app can create', () => {
        for (const type of ['SNAKES_LADDERS', 'SKILL_RACE', 'BINGO', 'DROP_RACE']) {
            const meta = eventTypeMeta(type);

            expect(meta, type).toBeTruthy();
            expect(meta.icon, type).toMatch(/^i-/);
            expect(meta.label, type).toBeTruthy();
        }
    });

    it('is null for a type it does not know', () => {
        expect(eventTypeMeta('QUIZ_NIGHT')).toBeNull();
    });
});
