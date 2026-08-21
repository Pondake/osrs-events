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
