import type {
  PlayerBoardEntity,
  RollResultEntity,
  UserEntity,
  PlayerBoardTeamSummary,
  LeaderboardEntryEntity,
  LeaderboardEntity,
} from '~/types/graphql'

// ─── Leaderboard types ────────────────────────────────────────────────────────
// PlayerBoardTeamSummary matches exactly — re-export under the old name for
// backwards compatibility with components that import TeamSummary from here.
export type { PlayerBoardTeamSummary as TeamSummary }

// LeaderboardEntryEntity exists in the schema but lacks `user` (the GQL query
// adds it via field selection). Extend rather than redefine.
export type LeaderboardEntry = LeaderboardEntryEntity & {
  user: Pick<UserEntity, 'id' | 'discordUsername' | 'nickname' | 'avatarUrl'>
}

// LeaderboardEntity exists but its entries array uses the base entity type.
// Override entries to carry the extended LeaderboardEntry.
export type LeaderboardData = Omit<LeaderboardEntity, 'entries'> & {
  entries: LeaderboardEntry[]
}

// ─── Field selections ────────────────────────────────────────────────────────

const PLAYER_BOARD_FIELDS = `
  id userId boardId teamId currentPosition diceRollsToday lastRollDate createdAt updatedAt
  completedTiles { id tileId completedAt completedVia }
  user { id discordUsername nickname avatarUrl }
  board { id title size }
  team { id name iconUrl }
`

// ─── Queries & mutations ─────────────────────────────────────────────────────

const MY_BOARD_STATE_QUERY = `
  query MyBoardState($boardId: ID!) {
    myBoardState(boardId: $boardId) { ${PLAYER_BOARD_FIELDS} }
  }
`

const MY_PLAYER_BOARDS_QUERY = `
  query MyPlayerBoards {
    myPlayerBoards { ${PLAYER_BOARD_FIELDS} }
  }
`

const BOARD_PLAYER_STATES_QUERY = `
  query BoardPlayerStates($boardId: ID!) {
    boardPlayerStates(boardId: $boardId) { ${PLAYER_BOARD_FIELDS} }
  }
`

const ROLL_DICE_MUTATION = `
  mutation RollDice($boardId: ID!) {
    rollDice(boardId: $boardId) {
      rolled previousPosition newPosition landedOn jump
      playerBoard { ${PLAYER_BOARD_FIELDS} }
    }
  }
`

const COMPLETE_TILE_MUTATION = `
  mutation CompleteTile($boardId: ID!, $tileId: ID!) {
    completeTile(boardId: $boardId, tileId: $tileId) { ${PLAYER_BOARD_FIELDS} }
  }
`

const UNCOMPLETE_TILE_MUTATION = `
  mutation UncompleteTile($boardId: ID!, $tileId: ID!) {
    uncompleteTile(boardId: $boardId, tileId: $tileId) { ${PLAYER_BOARD_FIELDS} }
  }
`

// ─── Composables ─────────────────────────────────────────────────────────────

/**
 * Manages the current player's state on a specific board.
 * Imperative (not SSR) — call load() in onMounted.
 *
 * @example
 * const { playerBoard, load, rollDice, completeTile } = usePlayerBoard(boardId)
 * onMounted(load)
 */
export function usePlayerBoard(boardId: string) {
  const playerBoard = ref<PlayerBoardEntity | null>(null)
  const loading = ref(false)
  const error = ref<Error | null>(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      const result = await useGqlMutation<{ myBoardState: PlayerBoardEntity | null }>(
        MY_BOARD_STATE_QUERY,
        { boardId },
      )
      playerBoard.value = result.myBoardState
    } catch (e) {
      error.value = e as Error
    } finally {
      loading.value = false
    }
  }

  async function rollDice(): Promise<RollResultEntity> {
    const result = await useGqlMutation<{ rollDice: RollResultEntity }>(ROLL_DICE_MUTATION, {
      boardId,
    })
    // Update local player board state from roll result
    playerBoard.value = result.rollDice.playerBoard
    return result.rollDice
  }

  async function completeTile(tileId: string): Promise<PlayerBoardEntity> {
    const result = await useGqlMutation<{ completeTile: PlayerBoardEntity }>(
      COMPLETE_TILE_MUTATION,
      { boardId, tileId },
    )
    playerBoard.value = result.completeTile
    return result.completeTile
  }

  async function uncompleteTile(tileId: string): Promise<PlayerBoardEntity | null> {
    const result = await useGqlMutation<{ uncompleteTile: PlayerBoardEntity | null }>(
      UNCOMPLETE_TILE_MUTATION,
      { boardId, tileId },
    )
    if (result.uncompleteTile) playerBoard.value = result.uncompleteTile
    return result.uncompleteTile
  }

  return { playerBoard, loading, error, load, rollDice, completeTile, uncompleteTile }
}

/**
 * All players' positions on a board — for leaderboard and multi-player view.
 * Imperative — call load() in onMounted.
 */
export function useBoardPlayerStates(boardId: string) {
  const playerStates = ref<PlayerBoardEntity[]>([])
  const loading = ref(false)
  const error = ref<Error | null>(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      const result = await useGqlMutation<{ boardPlayerStates: PlayerBoardEntity[] }>(
        BOARD_PLAYER_STATES_QUERY,
        { boardId },
      )
      playerStates.value = result.boardPlayerStates ?? []
    } catch (e) {
      error.value = e as Error
    } finally {
      loading.value = false
    }
  }

  return { playerStates, loading, error, load }
}

/**
 * All boards the current player has joined (for the profile page).
 * Imperative — call load() in onMounted.
 */
export function useMyPlayerBoards() {
  const playerBoards = ref<PlayerBoardEntity[]>([])
  const loading = ref(false)
  const error = ref<Error | null>(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      const result = await useGqlMutation<{ myPlayerBoards: PlayerBoardEntity[] }>(
        MY_PLAYER_BOARDS_QUERY,
      )
      playerBoards.value = result.myPlayerBoards ?? []
    } catch (e) {
      error.value = e as Error
    } finally {
      loading.value = false
    }
  }

  return { playerBoards, loading, error, load }
}

// ─── Leaderboard ──────────────────────────────────────────────────────────────

const LEADERBOARD_FIELDS = `
  boardId totalTiles
  entries {
    rank playerId currentPosition tilesRemaining pathHasLadder pathHasSnake
    user { id discordUsername nickname avatarUrl }
    team { id name iconUrl }
  }
`

const LEADERBOARD_QUERY = `
  query BoardLeaderboard($boardId: ID!) {
    boardLeaderboard(boardId: $boardId) { ${LEADERBOARD_FIELDS} }
  }
`

/**
 * Leaderboard for a specific board.
 * SSR-safe — fetches on the server and is reactive to boardId changes.
 *
 * @example
 * const { leaderboard, pending, refresh } = await useLeaderboard(boardId)
 */
export async function useLeaderboard(boardId: string) {
  const vars = computed(() => ({ boardId }))
  const { data, pending, error, refresh } = await useGql<{
    boardLeaderboard: LeaderboardData | null
  }>(LEADERBOARD_QUERY, vars)

  return {
    leaderboard: computed(() => data.value?.boardLeaderboard ?? null),
    entries: computed(() => data.value?.boardLeaderboard?.entries ?? []),
    pending,
    error,
    refresh,
  }
}

/**
 * Leaderboard helper — CSS class for "tiles remaining" based on path.
 */
export function leaderboardRemainingClass(entry: LeaderboardEntry): string {
  if (entry.pathHasSnake) return 'text-error'
  if (entry.pathHasLadder) return 'text-success'
  return 'text-muted'
}
