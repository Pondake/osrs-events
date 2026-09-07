import { describe, expect, it, vi } from 'vitest';
import { trans } from 'laravel-vue-i18n';

import { BOARD_TILE_COUNT, boardEventStatus, claimAreaIsShown, eventStatus, finishSubtitle, formatBoardSize, formatDate, ordinal, settledPlace } from '@/Support/board';
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

    // An event stopped by a finish, or by a host calling it, is over on a
    // date it was still due to run. This mirrors Event::isEnded() folding the
    // same column in — the page has to agree with the server about it, or it
    // keeps offering a dice every mutation endpoint is already refusing.
    it('reports a closed event as ended even mid-run', () => {
        expect(eventStatus({ ...running, closed_at: '2026-08-23T10:00:00Z' }, now)).toBe('ended');
    });

    it('prefers closed over paused', () => {
        expect(eventStatus({
            ...running,
            paused_at: '2026-08-20T09:00:00Z',
            closed_at: '2026-08-23T10:00:00Z',
        }, now)).toBe('ended');
    });

    it('leaves an open event alone when closed_at is null', () => {
        expect(eventStatus({ ...running, closed_at: null }, now)).toBe('live');
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
     * Bosses had none for a long time: the icon set is built from wiki item
     * and category images and there is no "Zulrah icon". The PET is the
     * answer — one sprite per boss, unambiguous. See BOSS_PETS in
     * scripts/extract-osrs-icons.mjs.
     */
    it('points at the committed pet sprite for a boss', () => {
        expect(metricIconUrl('zulrah', 'boss')).toBe('/images/osrs/bosses/zulrah.png');
    });

    /**
     * Not every boss has one — Barrows and the Mimic drop no pet at all, and
     * the newest bosses are not in the package yet. Those must answer null
     * rather than point at a file that was never written, which would render
     * as a broken image.
     */
    it('has nothing for a boss without a pet', () => {
        expect(metricIconUrl('barrows_chests', 'boss')).toBeNull();
        expect(metricIconUrl('mimic', 'boss')).toBeNull();
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

describe('ordinal', () => {
    // Reported directly: the finish card read "1 place", because a bare rank
    // went into a ":place place" string.
    it('spells the ordinary cases', () => {
        expect(ordinal(1)).toBe('1st');
        expect(ordinal(2)).toBe('2nd');
        expect(ordinal(3)).toBe('3rd');
        expect(ordinal(4)).toBe('4th');
    });

    // The whole reason this is a function and not a lookup — and the half
    // every naive version gets wrong.
    it('does not say 11st, 12nd or 13rd', () => {
        expect(ordinal(11)).toBe('11th');
        expect(ordinal(12)).toBe('12th');
        expect(ordinal(13)).toBe('13th');
        expect(ordinal(21)).toBe('21st');
        expect(ordinal(111)).toBe('111th');
        expect(ordinal(112)).toBe('112th');
    });

    // A rank that is not there yet must render as nothing, not "undefinedth".
    it('says nothing about a missing place', () => {
        expect(ordinal(null)).toBe('');
        expect(ordinal(undefined)).toBe('');
    });
});

/**
 * Which place a finish may be shown as.
 *
 * Found by walking an event end to end as two racers and a host: the host
 * approved the later of two finishing claims first, and while the earlier one
 * was still unopened the sidebar ranking and the leaderboard page both handed
 * a gold medal to the wrong competitor. The finish card on the same screen
 * was already refusing to name a place, which is what made it a bug rather
 * than a decision.
 */
describe('settledPlace', () => {
    it('gives a settled finish its rank', () => {
        expect(settledPlace({ rank: 1, provisional: false })).toBe(1);
        expect(settledPlace({ rank: 4, provisional: false })).toBe(4);
    });

    // The reported failure, in one line: home, but not in a place yet.
    it('withholds the place while an earlier claim is unreviewed', () => {
        expect(settledPlace({ rank: 1, provisional: true })).toBeNull();
    });

    // Only `true` withholds. A payload that predates the flag, or a finish
    // sent without it, must still show the place it has — silently blanking
    // every podium would be a worse failure than the one this prevents.
    it('treats a missing flag as settled', () => {
        expect(settledPlace({ rank: 2 })).toBe(2);
        expect(settledPlace({ rank: 2, provisional: undefined })).toBe(2);
    });

    it('has nothing to say about somebody who has not finished', () => {
        expect(settledPlace(null)).toBeNull();
        expect(settledPlace(undefined)).toBeNull();
    });
});

/**
 * The line under your own place once the event is over.
 *
 * Same walkthrough: second place was told "That was the winning run", which
 * on a STOP event is by definition somebody else's run — the one that ended
 * the event over them.
 */
describe('finishSubtitle', () => {
    it('tells the winner it was the winning run', () => {
        expect(finishSubtitle('2026-09-07T07:15:59Z', 1, '2026-09-13')).toBe('t:board.finished_closed');
    });

    it('does not tell everybody else the same thing', () => {
        expect(finishSubtitle('2026-09-07T07:15:59Z', 2, '2026-09-13')).toBe('t:board.finished_closed_behind');
        expect(finishSubtitle('2026-09-07T07:15:59Z', 7, '2026-09-13')).toBe('t:board.finished_closed_behind');
    });

    // Still running: nobody has won anything, so the card points at the end
    // date instead of at a result.
    it('points an open event at its end date', () => {
        expect(finishSubtitle(null, 1, '2026-09-13')).toBe('t:board.finished_continue');
        expect(trans).toHaveBeenCalledWith('board.finished_continue', { when: formatDate('2026-09-13') });
    });
});

/**
 * Whether the tile card still has something to show.
 *
 * The same walkthrough again, at its last step: the host rejected the claim
 * on the final tile, which took the win back, and the player was shown
 * nothing at all — no status, no note, no way to open the claim — because the
 * event was closed by then and one guard was hiding the verdict along with
 * the button.
 */
describe('claimAreaIsShown', () => {
    const live = { start_date: '2026-09-01', end_date: '2026-09-30' };
    const now = new Date('2026-09-07T12:00:00Z');
    const claim = { id: 'c1', status: 'REJECTED', reviewNote: 'Wrong account.' };

    it('shows the area on a live event with nothing claimed yet', () => {
        expect(claimAreaIsShown(live, null, now)).toBe(true);
    });

    // The fix: a verdict already given does not expire with the event.
    it('keeps a claim readable after the event has closed', () => {
        expect(claimAreaIsShown({ ...live, closed_at: '2026-09-07T07:15:59Z' }, claim, now)).toBe(true);
        expect(claimAreaIsShown({ start_date: '2026-08-01', end_date: '2026-08-20' }, claim, now)).toBe(true);
        expect(claimAreaIsShown({ ...live, paused_at: '2026-09-06' }, claim, now)).toBe(true);
    });

    // Nothing claimed and nothing to do: the card keeps its own counsel
    // rather than offering a button every endpoint would refuse.
    it('shows nothing on an event that is not taking moves', () => {
        expect(claimAreaIsShown({ ...live, closed_at: '2026-09-07T07:15:59Z' }, null, now)).toBe(false);
        expect(claimAreaIsShown({ ...live, paused_at: '2026-09-06' }, null, now)).toBe(false);
        expect(claimAreaIsShown({ start_date: '2026-10-01', end_date: '2026-10-30' }, null, now)).toBe(false);
    });
});
