// Ported from stale/frontend/app/utils/board.ts — same constants, same
// boardEventStatus() logic (UTC-day comparison so SSR and the client agree
// regardless of local timezone), i18n calls stripped since i18n isn't ported
// yet (see docs/backlog.md); component-side `key` fields below are plain
// English strings for now instead of translation keys.

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
    OPEN: { icon: 'i-lucide-globe', label: 'Open to everyone' },
    GUILD: { icon: 'i-lucide-shield', label: 'Discord server members only' },
    INVITE: { icon: 'i-lucide-lock', label: 'Invite only' },
};

export const BOARD_STATUS_STYLE = {
    upcoming: { icon: 'i-lucide-clock', label: 'Upcoming', class: 'bg-info/10 text-info' },
    live: { icon: 'i-lucide-circle-play', label: 'Live', class: 'bg-success/10 text-success' },
    ended: { icon: 'i-lucide-flag', label: 'Ended', class: 'bg-error/10 text-error' },
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
