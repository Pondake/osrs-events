// Ported from stale/frontend/app/utils/board.ts — same constants, same
// boardEventStatus() logic (UTC-day comparison so SSR and the client agree
// regardless of local timezone).
//
// The label fields below are translation KEYS, not English. They were plain
// strings while i18n was unported, and that outlived the port: a card and a
// detail page both render boardEventStatus() output, but the card read the
// English here and the page read `boards.status_*`, so the same running event
// was labelled "Live" on the hub and "Running" one click later. Keys here,
// $t() at the point of render, one wording.

import { trans } from 'laravel-vue-i18n';

export const BOARD_SIZE_LABEL = {
    SIZE_5X5: '5×5',
    SIZE_7X7: '7×7',
    SIZE_9X9: '9×9',
};

export const BOARD_TILE_COUNT = {
    SIZE_5X5: 25,
    SIZE_7X7: 49,
    SIZE_9X9: 81,
};

export const BOARD_MIN_WIDTH = {
    SIZE_5X5: 'min-w-[300px]',
    SIZE_7X7: 'min-w-[380px]',
    SIZE_9X9: 'min-w-[460px]',
};

export function formatBoardSize(size) {
    return BOARD_SIZE_LABEL[size] ?? size;
}

export function formatDate(date, style = 'short') {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: style === 'long' ? 'long' : 'short',
        year: 'numeric',
    });
}

export const BOARD_ACCESS_META = {
    OPEN: { icon: 'i-lucide-globe', labelKey: 'boards.access_open' },
    GUILD: { icon: 'i-lucide-shield', labelKey: 'boards.access_server' },
    INVITE: { icon: 'i-lucide-lock', labelKey: 'boards.access_invite' },
};

export const BOARD_STATUS_STYLE = {
    upcoming: { icon: 'i-lucide-clock', labelKey: 'boards.status_upcoming', class: 'bg-info/10 text-info' },
    live: { icon: 'i-lucide-circle-play', labelKey: 'boards.status_live', class: 'bg-success/10 text-success' },
    paused: { icon: 'i-lucide-pause', labelKey: 'boards.status_paused', class: 'bg-warning/10 text-warning' },
    ended: { icon: 'i-lucide-flag', labelKey: 'boards.status_ended', class: 'bg-error/10 text-error' },
};

function utcDay(date) {
    return new Date(date).toISOString().slice(0, 10);
}

export function boardEventStatus(startDate, endDate, now = new Date()) {
    const today = utcDay(now);
    if (endDate && utcDay(endDate) < today) return 'ended';
    if (startDate && utcDay(startDate) > today) return 'upcoming';
    return 'live';
}

/**
 * The same question, asked of a whole event rather than of two dates.
 *
 * A host can stop an event without moving its dates, so where it sits on the
 * calendar is no longer the whole answer. Kept as a wrapper rather than
 * folded into boardEventStatus(): that one is a pure function of the dates
 * and is tested as such, and every caller here has an event object to hand
 * anyway.
 *
 * `ended` outranks `paused`. An event that was paused and then ran past its
 * end date is over — saying "paused" about it would imply somebody is coming
 * back to start it again.
 */
/**
 * "3rd", not "3".
 *
 * The JS half of App\Support\Ordinal, and it exists for the same reason that
 * one does: 11, 12 and 13 break the pattern every naive version follows. Two
 * copies rather than one is unavoidable across a server/browser boundary —
 * the announcements are built in PHP and the cards in Vue — so the rule is at
 * least written down twice in the same words, and tested on both sides.
 *
 * Reported directly: the finish card read "1 place", because the rank went
 * into a `:place place` string as a bare number.
 */
export function ordinal(number) {
    if (number === null || number === undefined) return '';

    const suffix = [11, 12, 13].includes(number % 100)
        ? 'th'
        : ({ 1: 'st', 2: 'nd', 3: 'rd' }[number % 10] ?? 'th');

    return `${number}${suffix}`;
}

/**
 * The place a finish has earned, or null while that place can still move.
 *
 * Finishing and placing are two facts, and every surface that showed a
 * podium derived the second straight from the first. A finish is
 * **provisional** while a claim submitted earlier than it is still unreviewed:
 * approving that claim pushes this one down. The server already says so on
 * every finish it sends (EventFinishService::places()), and the finish card
 * on the event page already honoured it — the sidebar ranking and the
 * leaderboard page did not, so both crowned the wrong competitor with a medal
 * while the claim that actually won sat unopened in the host's queue.
 *
 * Returns the rank so a caller can medal it, or null so a caller can say
 * "home" without saying "first".
 */
export function settledPlace(finish) {
    if (finish === null || finish === undefined) return null;

    return finish.provisional === true ? null : (finish.rank ?? null);
}

/**
 * The line under your own place on a finish card.
 *
 * Only first place made the winning run. "That was the winning run — the
 * event is over" was printed for every finisher on a closed event, so on a
 * STOP event the runner-up was congratulated on winning by the very run that
 * had just beaten them.
 *
 * Takes the closed stamp and the rank rather than the whole event, so the
 * rule is one line to read and does not care where either came from.
 */
export function finishSubtitle(closedAt, rank, endDate) {
    if (!closedAt) return trans('board.finished_continue', { when: formatDate(endDate) });

    return rank === 1 ? trans('board.finished_closed') : trans('board.finished_closed_behind');
}

export function eventStatus(event, now = new Date()) {
    const byDate = boardEventStatus(event?.start_date, event?.end_date, now);

    if (byDate === 'ended') return 'ended';

    // Closed outranks paused for the same reason ended does, and outranks
    // the calendar entirely: an event stopped by a finish (or by a host
    // pressing End now) is over on a date it was still due to run. This
    // mirrors Event::isEnded() on the server, which folds the same column
    // in — without it the page would keep offering a dice on an event every
    // mutation endpoint has already started refusing.
    if (event?.closed_at) return 'ended';

    return event?.paused_at ? 'paused' : byDate;
}

/**
 * Whether the tile card has anything to put under the task at all.
 *
 * Two different questions used to share one guard, and the wrong one won.
 * *Making* a move needs a live event — paused, ended and not-yet-started all
 * refuse it. *Reading the verdict on a move already made* needs nothing: it
 * is a decision a host took about this player, and it does not expire with
 * the event.
 *
 * Walking an event to its end showed what that cost. The host rejected the
 * claim on the final tile — the ruling that had just taken the win away —
 * and because the event was closed by then, the player was shown no status,
 * no note explaining it and no way to open the claim. The one screen that
 * owed them an explanation had hidden it.
 */
export function claimAreaIsShown(event, claim, now = new Date()) {
    return Boolean(claim) || eventStatus(event, now) === 'live';
}
