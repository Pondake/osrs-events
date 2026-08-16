import type { BoardSize } from '~/types/graphql';

/** Human-readable board size labels */
export const BOARD_SIZE_LABEL: Record<string, string> = {
  SIZE_5X5: '5×5',
  SIZE_7X7: '7×7',
  SIZE_9X9: '9×9',
};

/** Total tile count per board size */
export const BOARD_TILE_COUNT: Record<string, number> = {
  SIZE_5X5: 25,
  SIZE_7X7: 49,
  SIZE_9X9: 81,
};

/** Min-width class for horizontal scroll on small screens */
export const BOARD_MIN_WIDTH: Record<string, string> = {
  SIZE_5X5: 'min-w-[300px]',
  SIZE_7X7: 'min-w-[380px]',
  SIZE_9X9: 'min-w-[460px]',
};

export function formatBoardSize(size: BoardSize | string): string {
  return BOARD_SIZE_LABEL[size] ?? size;
}

export function formatDate(
  date: string | null | undefined,
  style: 'short' | 'long' = 'short',
): string {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: style === 'long' ? 'long' : 'short',
    year: 'numeric',
  });
}

export interface BoardMeta {
  icon: string;
  /** i18n key — utils stay translation-free, the component calls t() */
  key: string;
}

/**
 * Every board shows its access mode, including OPEN. Showing it only for the
 * restricted modes made "nothing" ambiguous: it could mean open, or it could
 * mean the indicator had not rendered.
 */
export const BOARD_ACCESS_META: Record<string, BoardMeta> = {
  OPEN: { icon: 'i-lucide-globe', key: 'boards.access_open' },
  GUILD: { icon: 'i-lucide-shield', key: 'boards.access_server' },
  INVITE: { icon: 'i-lucide-lock', key: 'boards.access_invite' },
};

export type BoardEventStatus = 'upcoming' | 'live' | 'ended';

export interface BoardStatusStyle extends BoardMeta {
  /** Soft-tinted pill; status is the one thing worth pulling the eye. */
  class: string;
}

// lucide has no chequered flag — `flag` is the closest finish marker it ships.
export const BOARD_STATUS_STYLE: Record<BoardEventStatus, BoardStatusStyle> = {
  upcoming: {
    icon: 'i-lucide-clock',
    key: 'boards.status_upcoming',
    class: 'bg-info/10 text-info',
  },
  live: {
    icon: 'i-lucide-circle-play',
    key: 'boards.status_live',
    class: 'bg-success/10 text-success',
  },
  ended: {
    icon: 'i-lucide-flag',
    key: 'boards.status_ended',
    class: 'bg-error/10 text-error',
  },
};

/** YYYY-MM-DD in UTC, so SSR and the client agree regardless of local timezone. */
function utcDay(date: string | Date): string {
  return new Date(date).toISOString().slice(0, 10);
}

/**
 * The card already prints the date range, but working out whether an event is
 * over requires reading two dates and comparing them to today. Compared in UTC
 * day precision to avoid a hydration mismatch between server and browser.
 */
export function boardEventStatus(
  startDate: string | null | undefined,
  endDate: string | null | undefined,
  now: Date = new Date(),
): BoardEventStatus {
  const today = utcDay(now);
  if (endDate && utcDay(endDate) < today) return 'ended';
  if (startDate && utcDay(startDate) > today) return 'upcoming';
  return 'live';
}
