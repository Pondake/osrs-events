import type { BoardSize } from '~/types/graphql'

/** Human-readable board size labels */
export const BOARD_SIZE_LABEL: Record<string, string> = {
  SIZE_5X5: '5×5',
  SIZE_7X7: '7×7',
  SIZE_9X9: '9×9',
}

/** Total tile count per board size */
export const BOARD_TILE_COUNT: Record<string, number> = {
  SIZE_5X5: 25,
  SIZE_7X7: 49,
  SIZE_9X9: 81,
}

/** Min-width class for horizontal scroll on small screens */
export const BOARD_MIN_WIDTH: Record<string, string> = {
  SIZE_5X5: 'min-w-[300px]',
  SIZE_7X7: 'min-w-[380px]',
  SIZE_9X9: 'min-w-[460px]',
}

export function formatBoardSize(size: BoardSize | string): string {
  return BOARD_SIZE_LABEL[size] ?? size
}

export function formatDate(
  date: string | null | undefined,
  style: 'short' | 'long' = 'short',
): string {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: style === 'long' ? 'long' : 'short',
    year: 'numeric',
  })
}
